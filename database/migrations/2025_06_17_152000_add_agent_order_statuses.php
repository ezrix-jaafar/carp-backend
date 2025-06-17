<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend the enum for order status to include agent_accepted and agent_rejected
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','awaiting_agent','assigned','agent_accepted','agent_rejected','picked_up','in_cleaning','hq_inspection','cleaned','delivered','invoiced','completed','cancelled','canceled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous set of enum values (without the agent statuses)
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','awaiting_agent','assigned','picked_up','in_cleaning','hq_inspection','cleaned','delivered','invoiced','completed','cancelled','canceled') NOT NULL DEFAULT 'pending'");
    }
};
