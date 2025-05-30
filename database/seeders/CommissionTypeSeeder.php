<?php

namespace Database\Seeders;

use App\Models\CommissionType;
use Illuminate\Database\Seeder;

class CommissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating commission types...');
        
        // Create default commission type (basic percentage)
        CommissionType::create([
            'name' => 'Standard Commission',
            'description' => 'Standard commission structure with fixed amount and percentage.',
            'fixed_amount' => 10.00,
            'percentage_rate' => 5.00,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->command->info('Created commission type: Standard Commission');
        
        // Create tiered commission type for high-value invoices
        CommissionType::create([
            'name' => 'Premium Commission',
            'description' => 'Higher rate for premium jobs with invoices over RM500.',
            'fixed_amount' => 15.00,
            'percentage_rate' => 7.50,
            'is_active' => true,
            'min_invoice_amount' => 500,
            'is_default' => false,
        ]);
        $this->command->info('Created commission type: Premium Commission');
        
        // Create tiered commission type for high-value invoices
        CommissionType::create([
            'name' => 'Luxury Commission',
            'description' => 'Premium rate for luxury jobs with invoices over RM1000.',
            'fixed_amount' => 25.00,
            'percentage_rate' => 10.00,
            'is_active' => true,
            'min_invoice_amount' => 1000,
            'is_default' => false,
        ]);
        $this->command->info('Created commission type: Luxury Commission');
        
        // Create fixed-only commission (no percentage)
        CommissionType::create([
            'name' => 'Fixed Commission',
            'description' => 'Fixed amount only, no percentage.',
            'fixed_amount' => 75.00,
            'percentage_rate' => 0.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->command->info('Created commission type: Fixed Commission');
        
        // Create percentage-only commission (no fixed amount)
        CommissionType::create([
            'name' => 'Percentage-Only Commission',
            'description' => 'Percentage of invoice only, no fixed amount.',
            'fixed_amount' => 0.00,
            'percentage_rate' => 12.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->command->info('Created commission type: Percentage-Only Commission');
        
        // Create capped commission (for invoices under a certain amount)
        CommissionType::create([
            'name' => 'Small Jobs Commission',
            'description' => 'Higher percentage for small jobs under RM300.',
            'fixed_amount' => 5.00,
            'percentage_rate' => 15.00,
            'is_active' => true,
            'max_invoice_amount' => 300,
            'is_default' => false,
        ]);
        $this->command->info('Created commission type: Small Jobs Commission');
        
        $this->command->info('Commission types created successfully.');
    }
}
