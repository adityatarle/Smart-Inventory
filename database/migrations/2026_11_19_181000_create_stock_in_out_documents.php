<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_receipts')) {
            Schema::create('stock_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('document_number')->unique();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('draft')->index(); // draft, submitted, cancelled
                $table->string('reference_number')->nullable();
                $table->date('transaction_date');
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_receipt_items')) {
            Schema::create('stock_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_receipt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_issues')) {
            Schema::create('stock_issues', function (Blueprint $table) {
                $table->id();
                $table->string('document_number')->unique();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('draft')->index();
                $table->string('reference_number')->nullable();
                $table->date('transaction_date');
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_issue_items')) {
            Schema::create('stock_issue_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_issue_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->timestamps();
            });
        }

        // Additive inventory permissions for receive/issue
        foreach (['inventory.receive', 'inventory.issue'] as $slug) {
            $exists = DB::table('permissions')->where('slug', $slug)->exists();
            if (! $exists) {
                $action = str_replace('inventory.', '', $slug);
                $id = DB::table('permissions')->insertGetId([
                    'name' => 'Inventory '.ucfirst($action),
                    'slug' => $slug,
                    'module' => 'inventory',
                    'description' => "Allow {$action} stock documents",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $roleIds = DB::table('roles')
                    ->whereIn('slug', ['super_admin', 'admin', 'manager', 'storekeeper'])
                    ->pluck('id');

                foreach ($roleIds as $roleId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $id],
                        []
                    );
                }

                // purchase_manager gets receive; sales_manager gets issue
                if ($slug === 'inventory.receive') {
                    $purchaseRole = DB::table('roles')->where('slug', 'purchase_manager')->value('id');
                    if ($purchaseRole) {
                        DB::table('permission_role')->updateOrInsert(
                            ['role_id' => $purchaseRole, 'permission_id' => $id],
                            []
                        );
                    }
                }
                if ($slug === 'inventory.issue') {
                    $salesRole = DB::table('roles')->where('slug', 'sales_manager')->value('id');
                    if ($salesRole) {
                        DB::table('permission_role')->updateOrInsert(
                            ['role_id' => $salesRole, 'permission_id' => $id],
                            []
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_issue_items');
        Schema::dropIfExists('stock_issues');
        Schema::dropIfExists('stock_receipt_items');
        Schema::dropIfExists('stock_receipts');

        $ids = DB::table('permissions')->whereIn('slug', ['inventory.receive', 'inventory.issue'])->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
