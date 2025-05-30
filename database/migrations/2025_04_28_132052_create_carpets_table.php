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
        Schema::create('carpets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('qr_code')->unique();
            $table->enum('type', ['wool', 'synthetic', 'silk', 'cotton', 'other']);
            $table->json('dimensions');
            $table->string('color');
            $table->enum('status', ['pending', 'picked_up', 'in_cleaning', 'cleaned', 'delivered'])->default('pending');
            $table->text('notes')->nullable();
            $table->decimal('additional_charges', 10, 2)->default(0.00);
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpets');
    }
};
