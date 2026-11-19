<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouses', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('address');
            }
            if (! Schema::hasColumn('warehouses', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('gst_number')->nullable();
                $table->text('billing_address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('pincode')->nullable();
                $table->string('payment_terms')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('company');
            }
            if (! Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('shipping_address');
            }
            if (! Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (! Schema::hasColumn('customers', 'pincode')) {
                $table->string('pincode')->nullable()->after('state');
            }
            if (! Schema::hasColumn('customers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('credit_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = collect(['gst_number', 'city', 'state', 'pincode', 'payment_terms'])
                ->filter(fn (string $column) => Schema::hasColumn('customers', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::dropIfExists('suppliers');

        Schema::table('warehouses', function (Blueprint $table) {
            $columns = collect(['contact_person', 'email'])
                ->filter(fn (string $column) => Schema::hasColumn('warehouses', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
