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
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('commission_type_id')->nullable()->after('invoice_id')->constrained();
            $table->string('commission_type_name')->nullable()->after('commission_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['commission_type_id']);
            $table->dropColumn(['commission_type_id', 'commission_type_name']);
        });
    }
};
