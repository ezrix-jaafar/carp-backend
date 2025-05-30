<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the current enum values for the status column
        $currentValues = DB::select("SHOW COLUMNS FROM orders WHERE Field = 'status'")[0]->Type;
        
        // Extract the enum values
        preg_match("/^enum\((.*)\)$/", $currentValues, $matches);
        $enumValues = str_getcsv($matches[1], ',', "'");
        
        // Add hq_inspection if not already present
        if (!in_array('hq_inspection', $enumValues)) {
            // Add the new value to the enum
            $enumValues[] = 'hq_inspection';
            
            // Format values for SQL
            $enumValuesString = "'" . implode("', '", $enumValues) . "'";
            
            // Alter the table
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM($enumValuesString) NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the current enum values for the status column
        $currentValues = DB::select("SHOW COLUMNS FROM orders WHERE Field = 'status'")[0]->Type;
        
        // Extract the enum values
        preg_match("/^enum\((.*)\)$/", $currentValues, $matches);
        $enumValues = str_getcsv($matches[1], ',', "'");
        
        // Remove 'hq_inspection' value
        $enumValues = array_filter($enumValues, function($value) {
            return $value !== 'hq_inspection';
        });
        
        // Format values for SQL
        $enumValuesString = "'" . implode("', '", $enumValues) . "'";
        
        // Alter the table to remove the enum value
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM($enumValuesString) NOT NULL DEFAULT 'pending'");
    }
};
