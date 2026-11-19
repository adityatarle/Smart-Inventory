<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_code')) {
                $table->string('product_code')->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('product_code');
            }
            if (! Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'max_stock_level')) {
                $table->unsignedInteger('max_stock_level')->nullable()->after('reorder_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = collect(['product_code', 'barcode', 'image_path', 'max_stock_level'])
                ->filter(fn (string $column) => Schema::hasColumn('products', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
