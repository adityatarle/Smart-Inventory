<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partners', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('territory')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_persons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->constrained('sales_partners')->nullOnDelete();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->constrained('sales_partners')->nullOnDelete();
            $table->foreignId('sales_person_id')->nullable()->constrained('sales_persons')->nullOnDelete();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blanket_orders', function (Blueprint $table) {
            $table->id();
            $table->string('blanket_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_person_id')->nullable()->constrained('sales_persons')->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->constrained('sales_partners')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blanket_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blanket_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('agreed_quantity');
            $table->unsignedInteger('ordered_quantity')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_person_id')->nullable()->constrained('sales_persons')->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->constrained('sales_partners')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->date('valid_until')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreignId('sales_person_id')->nullable()->after('customer_id')->constrained('sales_persons')->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->after('sales_person_id')->constrained('sales_partners')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->after('sales_partner_id')->constrained('quotations')->nullOnDelete();
            $table->foreignId('blanket_order_id')->nullable()->after('quotation_id')->constrained('blanket_orders')->nullOnDelete();
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_person_id')->nullable()->constrained('sales_persons')->nullOnDelete();
            $table->foreignId('sales_partner_id')->nullable()->constrained('sales_partners')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropConstrainedForeignId('sales_person_id');
            $table->dropConstrainedForeignId('sales_partner_id');
            $table->dropConstrainedForeignId('quotation_id');
            $table->dropConstrainedForeignId('blanket_order_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('blanket_order_items');
        Schema::dropIfExists('blanket_orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('sales_persons');
        Schema::dropIfExists('sales_partners');
    }
};
