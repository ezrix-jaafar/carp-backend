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
        Schema::table('carpets', function (Blueprint $table) {
            // Remove legacy fields once data migration is complete
            // This should be run after ensuring all data is properly migrated
            // to the new fields (carpet_type_id, width, length)
            $table->dropColumn([
                'type',     // Replaced by carpet_type_id
                'dimensions' // Replaced by width and length fields
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpets', function (Blueprint $table) {
            // Restore legacy fields if we need to rollback
            $table->string('type')->nullable();
            $table->json('dimensions')->nullable();
        });
    }
};
