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
        Schema::table('orders', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('pickup_address_id')->nullable()->after('pickup_date')->constrained('addresses');
            $table->foreignId('delivery_address_id')->nullable()->after('delivery_date')->constrained('addresses');
            
            // Note: We're not dropping the existing columns yet
            // This will be done in a separate migration after data migration is complete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pickup_address_id']);
            $table->dropForeign(['delivery_address_id']);
            $table->dropColumn(['pickup_address_id', 'delivery_address_id']);
        });
    }
};
