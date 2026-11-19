<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_stocks', 'average_cost')) {
                $table->decimal('average_cost', 14, 4)->default(0)->after('reserved_quantity');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'document_number')) {
                $table->string('document_number')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('stock_movements', 'transaction_date')) {
                $table->timestamp('transaction_date')->nullable()->after('document_number')->index();
            }
            if (! Schema::hasColumn('stock_movements', 'quantity_in')) {
                $table->unsignedInteger('quantity_in')->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('stock_movements', 'quantity_out')) {
                $table->unsignedInteger('quantity_out')->default(0)->after('quantity_in');
            }
            if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 14, 4)->default(0)->after('quantity_out');
            }
            if (! Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 14, 4)->default(0)->after('unit_cost');
            }
            if (! Schema::hasColumn('stock_movements', 'direction')) {
                $table->string('direction', 10)->nullable()->after('type')->index();
            }
            if (! Schema::hasColumn('stock_movements', 'status')) {
                $table->string('status', 20)->default('posted')->after('direction')->index();
            }
        });

        // Backfill ledger columns from legacy signed quantity rows.
        DB::table('stock_movements')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $qty = (int) $row->quantity;
                $in = $qty > 0 ? $qty : 0;
                $out = $qty < 0 ? abs($qty) : 0;

                DB::table('stock_movements')->where('id', $row->id)->update([
                    'document_number' => $row->document_number ?: ('LEG-'.$row->id),
                    'transaction_date' => $row->transaction_date ?: $row->created_at,
                    'quantity_in' => $in,
                    'quantity_out' => $out,
                    'direction' => $row->direction ?: ($qty >= 0 ? 'in' : 'out'),
                    'status' => $row->status ?: 'posted',
                    'unit_cost' => $row->unit_cost ?? 0,
                    'total_cost' => $row->total_cost ?? 0,
                ]);
            }
        });

        // Seed average_cost from product cost_price for existing balances.
        $stocks = DB::table('warehouse_stocks')
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->select('warehouse_stocks.id', 'products.cost_price')
            ->get();

        foreach ($stocks as $stock) {
            DB::table('warehouse_stocks')->where('id', $stock->id)->update([
                'average_cost' => $stock->cost_price ?? 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $columns = collect([
                'document_number', 'transaction_date', 'quantity_in', 'quantity_out',
                'unit_cost', 'total_cost', 'direction', 'status',
            ])->filter(fn ($c) => Schema::hasColumn('stock_movements', $c))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_stocks', 'average_cost')) {
                $table->dropColumn('average_cost');
            }
        });
    }
};
