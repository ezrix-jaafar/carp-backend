<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the current allowed values for the status column
        $table = DB::getTablePrefix() . 'orders';
        $column = 'status';
        $checkConstraint = DB::select("SHOW CREATE TABLE {$table}")[0]->{'Create Table'};
        
        // Extract the ENUM values
        if (preg_match('/`status` enum\((.*)\)/', $checkConstraint, $matches)) {
            $currentValues = $matches[1];
            // Add the new value to the ENUM if it doesn't already exist
            if (!str_contains($currentValues, "'awaiting_agent'")) {
                // Convert to array, add new value, and convert back to string
                $values = explode(',', $currentValues);
                $values[] = "'awaiting_agent'";
                $newValues = implode(',', $values);
                
                // Alter the table to update the ENUM
                DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} ENUM({$newValues})");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For safety, we won't remove the enum value in down() as it might cause data loss
        // if there are orders using the new status
    }
};
