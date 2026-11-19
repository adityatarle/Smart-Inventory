<?php

namespace Database\Seeders;

use App\Models\BlanketOrder;
use App\Models\BlanketOrderItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        User::updateOrCreate(
            ['email' => 'customer@smart.test'],
            [
                'name' => 'Demo Customer',
                'role' => 'customer',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin = User::where('email', 'admin@smart.test')->first()
            ?? User::where('email', 'admin@smartstock.test')->first();
        $staff = User::where('email', 'staff@smart.test')->first()
            ?? User::where('email', 'storekeeper@smartstock.test')->first();
        $customerUser = User::where('email', 'customer@smart.test')->first();

        $main = Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Distribution Hub',
                'address' => '12 Industrial Avenue',
                'city' => 'Metro City',
                'phone' => '+1-555-0100',
                'is_active' => true,
            ]
        );

        $east = Warehouse::updateOrCreate(
            ['code' => 'WH-EAST'],
            [
                'name' => 'East Fulfillment Center',
                'address' => '88 Harbor Road',
                'city' => 'Baytown',
                'phone' => '+1-555-0101',
                'is_active' => true,
            ]
        );

        $electronics = Category::updateOrCreate(
            ['slug' => 'electronics'],
            ['name' => 'Electronics', 'description' => 'Devices and accessories']
        );

        $supplies = Category::updateOrCreate(
            ['slug' => 'office-supplies'],
            ['name' => 'Office Supplies', 'description' => 'Everyday office consumables']
        );

        $products = [
            ['sku' => 'EL-1001', 'name' => 'Wireless Mouse', 'category_id' => $electronics->id, 'cost_price' => 8.50, 'sell_price' => 19.99, 'reorder_level' => 20],
            ['sku' => 'EL-1002', 'name' => 'USB-C Hub', 'category_id' => $electronics->id, 'cost_price' => 18.00, 'sell_price' => 39.99, 'reorder_level' => 15],
            ['sku' => 'OF-2001', 'name' => 'A4 Copy Paper (Ream)', 'category_id' => $supplies->id, 'cost_price' => 3.20, 'sell_price' => 6.50, 'reorder_level' => 40],
            ['sku' => 'OF-2002', 'name' => 'Ballpoint Pen Pack', 'category_id' => $supplies->id, 'cost_price' => 1.10, 'sell_price' => 2.99, 'reorder_level' => 50],
        ];

        $stockService = app(StockService::class);
        $productModels = [];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    ...$data,
                    'unit' => 'pcs',
                    'description' => $data['name'].' stock item',
                    'is_active' => true,
                ]
            );
            $productModels[] = $product;

            foreach ([[$main, 100], [$east, 40]] as [$warehouse, $qty]) {
                $exists = $product->stocks()->where('warehouse_id', $warehouse->id)->exists();
                if (! $exists) {
                    $stockService->adjust($warehouse->id, $product->id, $qty, 'in', null, $admin?->id, 'Initial seed stock');
                }
            }
        }

        $partner = SalesPartner::updateOrCreate(
            ['code' => 'SP-NORTH'],
            [
                'name' => 'North Channel Partners',
                'email' => 'north@partners.test',
                'phone' => '+1-555-0200',
                'territory' => 'North Region',
                'commission_rate' => 7.5,
                'is_active' => true,
            ]
        );

        $person = SalesPerson::updateOrCreate(
            ['code' => 'SE-001'],
            [
                'name' => 'Alex Seller',
                'email' => 'alex@smart.test',
                'phone' => '+1-555-0201',
                'user_id' => $staff?->id,
                'sales_partner_id' => $partner->id,
                'commission_rate' => 3.5,
                'is_active' => true,
            ]
        );

        $customer = Customer::updateOrCreate(
            ['code' => 'CUS-DEMO'],
            [
                'name' => 'Demo Customer Co',
                'email' => 'customer@smart.test',
                'phone' => '+1-555-0300',
                'company' => 'Demo Customer Co',
                'billing_address' => '100 Market Street',
                'shipping_address' => '100 Market Street',
                'user_id' => $customerUser?->id,
                'sales_partner_id' => $partner->id,
                'sales_person_id' => $person->id,
                'credit_limit' => 25000,
                'is_active' => true,
            ]
        );

        if (! Quotation::where('quotation_number', 'QT-SEED-001')->exists()) {
            $quotation = Quotation::create([
                'quotation_number' => 'QT-SEED-001',
                'customer_id' => $customer->id,
                'warehouse_id' => $main->id,
                'sales_person_id' => $person->id,
                'sales_partner_id' => $partner->id,
                'created_by' => $admin->id,
                'status' => 'sent',
                'valid_until' => now()->addDays(20),
                'total' => 119.94,
                'notes' => 'Seed quotation',
            ]);

            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id' => $productModels[0]->id,
                'quantity' => 6,
                'unit_price' => 19.99,
            ]);
        }

        if (! BlanketOrder::where('blanket_number', 'BO-SEED-001')->exists()) {
            $blanket = BlanketOrder::create([
                'blanket_number' => 'BO-SEED-001',
                'customer_id' => $customer->id,
                'warehouse_id' => $main->id,
                'sales_person_id' => $person->id,
                'sales_partner_id' => $partner->id,
                'created_by' => $admin->id,
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'total_value' => 999.00,
                'notes' => 'Annual supply agreement',
            ]);

            BlanketOrderItem::create([
                'blanket_order_id' => $blanket->id,
                'product_id' => $productModels[2]->id,
                'agreed_quantity' => 500,
                'ordered_quantity' => 0,
                'unit_price' => 6.50,
            ]);

            BlanketOrderItem::create([
                'blanket_order_id' => $blanket->id,
                'product_id' => $productModels[3]->id,
                'agreed_quantity' => 300,
                'ordered_quantity' => 0,
                'unit_price' => 2.99,
            ]);
        }

        // Keep DocumentNumber available for future seeds
        DocumentNumber::make('SEED');
    }
}
