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
        // Add agent_pickup and hq_pickup to orders.status enum
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','awaiting_agent','assigned','agent_accepted','agent_rejected','agent_pickup','hq_pickup','picked_up','in_cleaning','hq_inspection','cleaned','delivered','invoiced','completed','cancelled','canceled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove agent_pickup and hq_pickup from enum
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','awaiting_agent','assigned','agent_accepted','agent_rejected','picked_up','in_cleaning','hq_inspection','cleaned','delivered','invoiced','completed','cancelled','canceled') NOT NULL DEFAULT 'pending'");
    }
};
