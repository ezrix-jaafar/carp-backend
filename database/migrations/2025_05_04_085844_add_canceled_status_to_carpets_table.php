<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL specific code to modify ENUM values for the status column
        DB::statement("ALTER TABLE carpets MODIFY COLUMN status ENUM('pending', 'picked_up', 'hq_inspection', 'in_cleaning', 'cleaned', 'delivered', 'canceled') DEFAULT 'pending';");
        
        // Update any records with status 'cancelled' to 'canceled' if they exist
        // (Handling any potential previous data that might have been in 'cancelled')
        DB::statement("UPDATE carpets SET status = 'canceled' WHERE status = 'cancelled';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpets', function (Blueprint $table) {
            //
        });
    }
};
