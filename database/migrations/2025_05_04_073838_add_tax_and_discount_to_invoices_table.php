<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->after('total_amount')->nullable();
            $table->decimal('discount', 10, 2)->after('subtotal')->default(0);
            $table->string('discount_type')->after('discount')->default('fixed'); // fixed or percentage
            $table->decimal('tax_amount', 10, 2)->after('discount_type')->default(0);
            $table->unsignedBigInteger('tax_setting_id')->after('tax_amount')->nullable();
            $table->foreign('tax_setting_id')->references('id')->on('tax_settings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['tax_setting_id']);
            $table->dropColumn([
                'subtotal',
                'discount',
                'discount_type',
                'tax_amount',
                'tax_setting_id',
            ]);
        });
    }
};
