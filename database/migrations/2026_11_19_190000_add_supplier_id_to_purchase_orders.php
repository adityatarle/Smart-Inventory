<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_orders', 'supplier_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('ordered_by')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            });
        }

        // Best-effort backfill: match legacy supplier_name to suppliers by code/name/company.
        // Keeps supplier_name intact for historical display.
        if (Schema::hasColumn('purchase_orders', 'supplier_id')) {
            $suppliers = DB::table('suppliers')->get(['id', 'code', 'name', 'company_name']);

            foreach ($suppliers as $supplier) {
                $names = collect([$supplier->code, $supplier->name, $supplier->company_name])
                    ->filter(fn ($v) => filled($v))
                    ->unique()
                    ->values()
                    ->all();

                if ($names === []) {
                    continue;
                }

                DB::table('purchase_orders')
                    ->whereNull('supplier_id')
                    ->whereIn('supplier_name', $names)
                    ->update(['supplier_id' => $supplier->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'supplier_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('supplier_id');
            });
        }
    }
};
