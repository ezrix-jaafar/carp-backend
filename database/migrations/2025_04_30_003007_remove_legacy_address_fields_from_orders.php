<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This migration should only be run after all legacy address data
     * has been migrated to the addresses table.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_address',
                'delivery_address',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_address')->nullable()->after('pickup_date');
            $table->string('delivery_address')->nullable()->after('delivery_date');
        });
    }
};
