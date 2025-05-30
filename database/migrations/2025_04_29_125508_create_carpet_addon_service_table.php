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
        Schema::create('carpet_addon_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carpet_id')->constrained()->onDelete('cascade');
            $table->foreignId('addon_service_id')->constrained()->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate assignments
            $table->unique(['carpet_id', 'addon_service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpet_addon_service');
    }
};
