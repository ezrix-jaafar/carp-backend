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
            // First get current schema to understand what fields exist
            // and how we need to modify them
            $columns = Schema::getColumnListing('carpets');
            
            // Add the new fields
            $table->foreignId('carpet_type_id')->nullable()->constrained()->after('order_id')->nullOnDelete();
            $table->decimal('width', 8, 2)->nullable()->after('carpet_type_id');
            $table->decimal('length', 8, 2)->nullable()->after('width');
            
            // We'll keep the existing type field temporarily to enable data migration
            // It will be removed in a separate migration after data is transferred
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpets', function (Blueprint $table) {
            // Drop the newly added columns
            $table->dropForeign(['carpet_type_id']);
            $table->dropColumn(['carpet_type_id', 'width', 'length']);
        });
    }
};
