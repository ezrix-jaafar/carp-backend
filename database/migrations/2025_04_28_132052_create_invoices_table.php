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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'partially_paid', 'cancelled'])->default('pending');
            $table->dateTime('issued_at');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('status');
            $table->unique('order_id', 'order_invoice_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
