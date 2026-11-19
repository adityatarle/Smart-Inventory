<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('supplier_name');
            }
            if (! Schema::hasColumn('purchase_orders', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('order_date');
            }
            if (! Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('purchase_orders', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('purchase_orders', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('purchase_orders', 'terms')) {
                $table->text('terms')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('purchase_orders', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('ordered_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_orders', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
            if (! Schema::hasColumn('purchase_orders', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('purchase_orders', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('purchase_orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('purchase_orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('received_at');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'discount_percent')) {
                $table->decimal('discount_percent', 8, 2)->default(0)->after('unit_cost');
            }
            if (! Schema::hasColumn('purchase_order_items', 'tax_percent')) {
                $table->decimal('tax_percent', 8, 2)->default(0)->after('discount_percent');
            }
            if (! Schema::hasColumn('purchase_order_items', 'line_subtotal')) {
                $table->decimal('line_subtotal', 14, 2)->default(0)->after('tax_percent');
            }
            if (! Schema::hasColumn('purchase_order_items', 'line_total')) {
                $table->decimal('line_total', 14, 2)->default(0)->after('line_subtotal');
            }
            if (! Schema::hasColumn('purchase_order_items', 'rejected_quantity')) {
                $table->unsignedInteger('rejected_quantity')->default(0)->after('received_quantity');
            }
        });

        // Remap legacy statuses to Phase 4 workflow
        DB::table('purchase_orders')->where('status', 'ordered')->update(['status' => 'approved']);
        DB::table('purchase_orders')->where('status', 'received')->update(['status' => 'fully_received']);
        DB::table('purchase_orders')->whereNull('order_date')->update([
            'order_date' => DB::raw('DATE(COALESCE(ordered_at, created_at))'),
        ]);
        DB::table('purchase_orders')->where(function ($q) {
            $q->whereNull('subtotal')->orWhere('subtotal', 0);
        })->update([
            'subtotal' => DB::raw('total'),
        ]);

        if (! Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('document_number')->unique();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('draft')->index(); // draft, submitted, cancelled
                $table->date('receipt_date');
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('ordered_quantity')->default(0);
                $table->unsignedInteger('previously_received')->default(0);
                $table->unsignedInteger('received_quantity')->default(0);
                $table->unsignedInteger('rejected_quantity')->default(0);
                $table->unsignedInteger('accepted_quantity')->default(0);
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->string('batch_reference')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'purchase_receive_tolerance_pct'],
            ['value' => '0', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');

        Schema::table('purchase_order_items', function (Blueprint $table) {
            foreach (['discount_percent', 'tax_percent', 'line_subtotal', 'line_total', 'rejected_quantity'] as $col) {
                if (Schema::hasColumn('purchase_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach ([
                'order_date', 'expected_delivery_date', 'subtotal', 'discount_amount', 'tax_amount', 'terms',
                'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason', 'cancelled_at',
            ] as $col) {
                if (Schema::hasColumn('purchase_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (['submitted_by', 'approved_by', 'rejected_by'] as $fk) {
                if (Schema::hasColumn('purchase_orders', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
        });
    }
};
