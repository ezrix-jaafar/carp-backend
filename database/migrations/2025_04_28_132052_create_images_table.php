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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carpet_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('filename');
            $table->string('disk')->default('r2');
            $table->integer('size');
            $table->string('mime_type');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            
            $table->index('carpet_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
