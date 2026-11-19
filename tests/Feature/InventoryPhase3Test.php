<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\StockAdjustmentService;
use App\Services\StockInService;
use App\Services\StockOutService;
use App\Services\StockTransferService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class InventoryPhase3Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouseA;
    private Warehouse $warehouseB;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        Auth::login($this->admin);

        $this->warehouseA = Warehouse::query()->create([
            'name' => 'Warehouse A',
            'code' => 'WHA',
            'is_active' => true,
        ]);
        $this->warehouseB = Warehouse::query()->create([
            'name' => 'Warehouse B',
            'code' => 'WHB',
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'sku' => 'SKU-1',
            'name' => 'Test Product',
            'unit' => 'pcs',
            'cost_price' => 10,
            'sell_price' => 15,
            'reorder_level' => 20,
            'max_stock_level' => 500,
            'is_active' => true,
        ]);
    }

    public function test_receive_100_units_updates_stock(): void
    {
        app(InventoryService::class)->receive(
            $this->warehouseA->id,
            $this->product->id,
            100,
            10.0,
            null,
            $this->admin->id
        );

        $this->assertSame(100, $this->qty($this->warehouseA->id));
    }

    public function test_issue_30_units_updates_stock(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);
        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 30, null, $this->admin->id);

        $this->assertSame(70, $this->qty($this->warehouseA->id));
    }

    public function test_oversell_is_rejected(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);
        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 30, null, $this->admin->id);

        $this->expectException(\InvalidArgumentException::class);
        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 80, null, $this->admin->id);
    }

    public function test_transfer_and_full_receive(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);

        $transfers = app(StockTransferService::class);
        $transfer = $transfers->createDraft(
            [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id,
                'transaction_date' => now()->toDateString(),
            ],
            [['product_id' => $this->product->id, 'quantity' => 20]]
        );
        $transfer = $transfers->submit($transfer);

        $this->assertSame(80, $this->qty($this->warehouseA->id));
        $this->assertSame(0, $this->qty($this->warehouseB->id));
        $this->assertSame(20, $transfer->totalInTransit());

        $item = $transfer->items()->first();
        $transfer = $transfers->receive($transfer, [['item_id' => $item->id, 'quantity' => 20]]);

        $this->assertSame('completed', $transfer->status);
        $this->assertSame(80, $this->qty($this->warehouseA->id));
        $this->assertSame(20, $this->qty($this->warehouseB->id));
    }

    public function test_partial_transfer_keeps_remainder_in_transit(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);

        $transfers = app(StockTransferService::class);
        $transfer = $transfers->createDraft(
            [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id,
                'transaction_date' => now()->toDateString(),
            ],
            [['product_id' => $this->product->id, 'quantity' => 20]]
        );
        $transfer = $transfers->submit($transfer);
        $item = $transfer->items()->first();
        $transfer = $transfers->receive($transfer, [['item_id' => $item->id, 'quantity' => 8]]);

        $this->assertSame('partially_received', $transfer->status);
        $this->assertSame(12, $transfer->totalInTransit());
        $this->assertSame(8, $this->qty($this->warehouseB->id));
    }

    public function test_adjustment_from_70_to_65(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);
        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 30, null, $this->admin->id);

        $svc = app(StockAdjustmentService::class);
        $adj = $svc->createDraft(
            [
                'warehouse_id' => $this->warehouseA->id,
                'transaction_date' => now()->toDateString(),
            ],
            [[
                'product_id' => $this->product->id,
                'physical_quantity' => 65,
                'reason' => 'count_variance',
            ]]
        );
        $adj = $svc->submit($adj);

        $this->assertSame(65, $this->qty($this->warehouseA->id));
        $this->assertSame('submitted', $adj->status);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'adjust',
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => -5,
        ]);
    }

    public function test_cancel_submitted_receipt_creates_reversal(): void
    {
        $stockIn = app(StockInService::class);
        $receipt = $stockIn->createDraft(
            [
                'warehouse_id' => $this->warehouseA->id,
                'transaction_date' => now()->toDateString(),
            ],
            [['product_id' => $this->product->id, 'quantity' => 50, 'unit_cost' => 12]]
        );
        $receipt = $stockIn->submit($receipt);
        $this->assertSame(50, $this->qty($this->warehouseA->id));

        $stockIn->cancel($receipt->fresh());
        $this->assertSame(0, $this->qty($this->warehouseA->id));
        $this->assertDatabaseHas('stock_movements', ['type' => 'reversal']);
    }

    public function test_duplicate_submit_does_not_double_post(): void
    {
        $stockIn = app(StockInService::class);
        $receipt = $stockIn->createDraft(
            [
                'warehouse_id' => $this->warehouseA->id,
                'transaction_date' => now()->toDateString(),
            ],
            [['product_id' => $this->product->id, 'quantity' => 40, 'unit_cost' => 10]]
        );
        $stockIn->submit($receipt);

        try {
            $stockIn->submit($receipt->fresh());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        $this->assertSame(40, $this->qty($this->warehouseA->id));
        $this->assertSame(1, StockMovement::query()->where('type', 'in')->count());
    }

    public function test_sequential_issues_cannot_oversell(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 10, 5.0, null, $this->admin->id);

        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 7, null, $this->admin->id);

        $this->expectException(\InvalidArgumentException::class);
        app(InventoryService::class)->issue($this->warehouseA->id, $this->product->id, 5, null, $this->admin->id);
    }

    public function test_unauthorized_user_cannot_submit_receipt(): void
    {
        $viewer = User::query()->create([
            'name' => 'Viewer',
            'email' => 'viewer@example.com',
            'password' => 'password',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->post(route('stock-receipts.store'), [
                'warehouse_id' => $this->warehouseA->id,
                'transaction_date' => now()->toDateString(),
                'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 1]],
                'submit' => 1,
            ])
            ->assertForbidden();
    }

    public function test_ledger_entries_are_immutable(): void
    {
        $movement = app(InventoryService::class)->receive(
            $this->warehouseA->id,
            $this->product->id,
            5,
            9.0,
            null,
            $this->admin->id
        );

        $this->expectException(LogicException::class);
        $movement->update(['notes' => 'tamper']);
    }

    public function test_weighted_average_cost_and_multi_warehouse_balances(): void
    {
        $inventory = app(InventoryService::class);
        $inventory->receive($this->warehouseA->id, $this->product->id, 100, 10.0, null, $this->admin->id);
        $inventory->receive($this->warehouseA->id, $this->product->id, 100, 20.0, null, $this->admin->id);

        $stockA = WarehouseStock::query()
            ->where('warehouse_id', $this->warehouseA->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertSame(200, (int) $stockA->quantity);
        $this->assertEqualsWithDelta(15.0, (float) $stockA->average_cost, 0.0001);

        $inventory->receive($this->warehouseB->id, $this->product->id, 50, 8.0, null, $this->admin->id);
        $this->assertSame(50, $this->qty($this->warehouseB->id));

        $valuation = app(StockValuationService::class);
        $this->assertEqualsWithDelta(200 * 15 + 50 * 8, $valuation->totalInventoryValue(), 0.01);
    }

    public function test_stock_out_service_blocks_oversell_on_submit(): void
    {
        app(InventoryService::class)->receive($this->warehouseA->id, $this->product->id, 5, 10.0, null, $this->admin->id);

        $stockOut = app(StockOutService::class);
        $issue = $stockOut->createDraft(
            [
                'warehouse_id' => $this->warehouseA->id,
                'transaction_date' => now()->toDateString(),
            ],
            [['product_id' => $this->product->id, 'quantity' => 9]]
        );

        $this->expectException(ValidationException::class);
        $stockOut->submit($issue);
    }

    private function qty(int $warehouseId): int
    {
        return (int) (WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $this->product->id)
            ->value('quantity') ?? 0);
    }
}
