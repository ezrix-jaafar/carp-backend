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
        // Add 'hq_inspection' to the carpet status enum
        $this->updateEnum(
            'carpets',
            'status',
            ["pending", "picked_up", "in_cleaning", "hq_inspection", "cleaned", "delivered"],
            ["pending", "picked_up", "in_cleaning", "cleaned", "delivered"]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'hq_inspection' from the carpet status enum
        $this->updateEnum(
            'carpets',
            'status',
            ["pending", "picked_up", "in_cleaning", "cleaned", "delivered"],
            ["pending", "picked_up", "in_cleaning", "hq_inspection", "cleaned", "delivered"]
        );
    }

    /**
     * Update an enum field with new values.
     * 
     * @param string $table Table name
     * @param string $field Field name
     * @param array $newValues New enum values
     * @param array $oldValues Old enum values
     * @return void
     */
    protected function updateEnum($table, $field, $newValues, $oldValues)
    {
        // Convert enum arrays to string format
        $newValuesStr = "'" . implode("', '", $newValues) . "'";
        
        // Get the current column definition but without the enum values 
        $column = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'")[0];
        $columnType = $column->Type;
        
        // Replace the current enum values with the new ones
        $enumDefinition = "enum({$newValuesStr})";
        
        // Modify the column
        DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$field} {$enumDefinition}");
    }
};
