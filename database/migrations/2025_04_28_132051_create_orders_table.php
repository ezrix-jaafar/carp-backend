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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('agent_id')->nullable()->constrained();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_cleaning', 'cleaned', 'delivered', 'completed', 'cancelled'])->default('pending');
            $table->date('pickup_date');
            $table->text('pickup_address');
            $table->date('delivery_date')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_carpets')->default(0);
            $table->string('reference_number')->unique();
            $table->timestamps();
            
            $table->index('client_id');
            $table->index('agent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
