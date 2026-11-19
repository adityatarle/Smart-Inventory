<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_alerts')) {
            Schema::create('stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('type')->index(); // low_stock, out_of_stock, reorder, excess
                $table->string('severity')->default('warning'); // info, warning, critical
                $table->string('title');
                $table->text('message')->nullable();
                $table->integer('quantity')->default(0);
                $table->integer('threshold')->nullable();
                $table->timestamp('resolved_at')->nullable()->index();
                $table->timestamps();

                $table->index(['warehouse_id', 'product_id', 'type', 'resolved_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
