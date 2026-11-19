<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseSupplierRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_requires_and_links_supplier(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->create([
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'code' => 'SUP-1',
            'name' => 'Acme Supplies',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'P-1',
            'name' => 'Widget',
            'unit' => 'pcs',
            'cost_price' => 5,
            'sell_price' => 8,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('purchase-orders.store'), [
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 5, 'discount_percent' => 0, 'tax_percent' => 0],
                ],
            ])
            ->assertRedirect();

        $order = PurchaseOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame($supplier->id, (int) $order->supplier_id);
        $this->assertSame('Acme Supplies', $order->supplier_name);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertTrue($supplier->purchaseOrders()->whereKey($order->id)->exists());
    }

    public function test_supplier_delete_blocked_when_referenced_by_po(): void
    {
        $supplier = Supplier::query()->create([
            'code' => 'SUP-2',
            'name' => 'Blocked Co',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->create([
            'name' => 'WH',
            'code' => 'WH1',
            'is_active' => true,
        ]);

        PurchaseOrder::query()->create([
            'order_number' => 'PO-TEST-1',
            'warehouse_id' => $warehouse->id,
            'ordered_by' => $admin->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'total' => 10,
            'subtotal' => 10,
            'ordered_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(SupplierService::class)->delete($supplier);
    }
}
