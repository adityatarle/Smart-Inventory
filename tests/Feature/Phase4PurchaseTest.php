<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase4PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private Warehouse $warehouse;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'po-admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->viewer = User::query()->create([
            'name' => 'Viewer',
            'email' => 'po-viewer@example.com',
            'password' => 'password',
            'role' => 'customer',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::query()->create([
            'name' => 'Main WH',
            'code' => 'MWH',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::query()->create([
            'code' => 'SUP-PO',
            'name' => 'PO Supplier',
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'sku' => 'PO-SKU',
            'name' => 'PO Product',
            'unit' => 'pcs',
            'cost_price' => 10,
            'sell_price' => 15,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        Auth::login($this->admin);
    }

    public function test_create_and_submit_purchase_order(): void
    {
        $svc = app(PurchaseOrderService::class);
        $order = $svc->createDraft($this->header(), [$this->line(10, 12)]);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);

        $order = $svc->submit($order);
        $this->assertSame(PurchaseOrder::STATUS_PENDING_APPROVAL, $order->status);
        $this->assertNotNull($order->submitted_by);
    }

    public function test_unauthorized_approval_rejected(): void
    {
        $svc = app(PurchaseOrderService::class);
        $order = $svc->createDraft($this->header(), [$this->line(5, 10)]);
        $svc->submit($order);

        $this->actingAs($this->viewer)
            ->post(route('purchase-orders.approve', $order))
            ->assertForbidden();

        $this->assertSame(PurchaseOrder::STATUS_PENDING_APPROVAL, $order->fresh()->status);
    }

    public function test_authorized_approval_succeeds(): void
    {
        $svc = app(PurchaseOrderService::class);
        $order = $svc->createDraft($this->header(), [$this->line(5, 10)]);
        $order = $svc->submit($order);
        $order = $svc->approve($order);

        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $order->status);
        $this->assertNotNull($order->approved_by);
    }

    public function test_full_and_partial_grn_and_multiple_receipts(): void
    {
        $order = $this->approvedOrder(20, 10.0);
        $item = $order->items()->first();
        $grnSvc = app(GoodsReceiptService::class);

        $grn1 = $grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 8, 0, 8),
        ]);
        $grn1 = $grnSvc->submit($grn1);

        $this->assertSame(GoodsReceipt::STATUS_SUBMITTED, $grn1->status);
        $this->assertSame(8, $this->qty());
        $this->assertSame(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $order->fresh()->status);

        $item = $item->fresh();
        $grn2 = $grnSvc->createDraft($order->fresh(), $this->grnHeader(), [
            $this->grnLine($item, 12, 0, 12),
        ]);
        $grnSvc->submit($grn2);

        $this->assertSame(20, $this->qty());
        $this->assertSame(PurchaseOrder::STATUS_FULLY_RECEIVED, $order->fresh()->status);
        $this->assertSame(2, StockMovement::query()->where('type', 'in')->count());
    }

    public function test_over_receiving_rejected(): void
    {
        $order = $this->approvedOrder(5, 10);
        $item = $order->items()->first();
        $grnSvc = app(GoodsReceiptService::class);

        $grn = $grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 9, 0, 9),
        ]);

        $this->expectException(ValidationException::class);
        $grnSvc->submit($grn);
    }

    public function test_duplicate_grn_submit_rejected(): void
    {
        $order = $this->approvedOrder(5, 10);
        $item = $order->items()->first();
        $grnSvc = app(GoodsReceiptService::class);
        $grn = $grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 5, 0, 5),
        ]);
        $grnSvc->submit($grn);

        $this->expectException(ValidationException::class);
        $grnSvc->submit($grn->fresh());
    }

    public function test_inventory_and_wac_updated_via_inventory_service(): void
    {
        $order = $this->approvedOrder(10, 20);
        $item = $order->items()->first();
        $grnSvc = app(GoodsReceiptService::class);
        $grn = $grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 10, 0, 10, 20),
        ]);
        $grnSvc->submit($grn);

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertSame(10, (int) $stock->quantity);
        $this->assertEqualsWithDelta(20.0, (float) $stock->average_cost, 0.0001);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'reference_type' => (new GoodsReceipt)->getMorphClass(),
            'quantity' => 10,
        ]);
    }

    public function test_rejected_quantities_do_not_enter_inventory(): void
    {
        $order = $this->approvedOrder(10, 10);
        $item = $order->items()->first();
        $grnSvc = app(GoodsReceiptService::class);
        $grn = $grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 10, 4, 6),
        ]);
        $grnSvc->submit($grn);

        $this->assertSame(6, $this->qty());
        $this->assertSame(6, (int) $item->fresh()->received_quantity);
        $this->assertSame(4, (int) $item->fresh()->rejected_quantity);
    }

    public function test_cancellation_preserves_history_and_blocks_supplier_delete(): void
    {
        $svc = app(PurchaseOrderService::class);
        $order = $svc->createDraft($this->header(), [$this->line(3, 5)]);
        $svc->cancel($order);

        $this->assertSame(PurchaseOrder::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id, 'status' => 'cancelled']);

        $this->expectException(ValidationException::class);
        app(SupplierService::class)->delete($this->supplier->fresh());
    }

    public function test_sequential_grn_cannot_duplicate_stock_beyond_ordered(): void
    {
        $order = $this->approvedOrder(5, 10);
        $grnSvc = app(GoodsReceiptService::class);
        $item = $order->items()->first();

        $grnSvc->submit($grnSvc->createDraft($order, $this->grnHeader(), [
            $this->grnLine($item, 5, 0, 5),
        ]));

        $this->expectException(ValidationException::class);
        $grnSvc->submit($grnSvc->createDraft($order->fresh(), $this->grnHeader(), [
            $this->grnLine($item->fresh(), 1, 0, 1),
        ]));
    }

    private function approvedOrder(int $qty, float $cost): PurchaseOrder
    {
        $svc = app(PurchaseOrderService::class);
        $order = $svc->createDraft($this->header(), [$this->line($qty, $cost)]);
        $order = $svc->submit($order);

        return $svc->approve($order);
    }

    private function header(): array
    {
        return [
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Phase 4 test',
            'terms' => 'Net 30',
        ];
    }

    private function line(int $qty, float $cost): array
    {
        return [
            'product_id' => $this->product->id,
            'quantity' => $qty,
            'unit_cost' => $cost,
            'discount_percent' => 0,
            'tax_percent' => 0,
        ];
    }

    private function grnHeader(): array
    {
        return [
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'remarks' => 'GRN test',
        ];
    }

    private function grnLine($item, int $received, int $rejected, int $accepted, ?float $cost = null): array
    {
        return [
            'purchase_order_item_id' => $item->id,
            'received_quantity' => $received,
            'rejected_quantity' => $rejected,
            'accepted_quantity' => $accepted,
            'unit_cost' => $cost ?? (float) $item->unit_cost,
            'batch_reference' => 'BATCH-1',
        ];
    }

    private function qty(): int
    {
        return (int) (WarehouseStock::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity') ?? 0);
    }
}
