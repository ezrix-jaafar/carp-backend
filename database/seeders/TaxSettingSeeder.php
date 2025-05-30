<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxSetting;

class TaxSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create common tax settings for Malaysia
        $taxSettings = [
            [
                'name' => 'SST (Sales and Service Tax)',
                'type' => 'percentage',
                'rate' => 6.0,
                'is_active' => true,
                'description' => 'Malaysia standard Sales and Service Tax at 6%',
            ],
            [
                'name' => 'SST (10%)',
                'type' => 'percentage',
                'rate' => 10.0,
                'is_active' => true,
                'description' => 'Malaysia Sales and Service Tax at 10% for specific services',
            ],
            [
                'name' => 'No Tax',
                'type' => 'fixed',
                'rate' => 0.0,
                'is_active' => true,
                'description' => 'No tax applied to invoice',
            ],
            [
                'name' => 'Service Charge',
                'type' => 'percentage',
                'rate' => 5.0,
                'is_active' => true,
                'description' => 'Standard service charge of 5%',
            ],
        ];
        
        foreach ($taxSettings as $setting) {
            TaxSetting::updateOrCreate(
                ['name' => $setting['name']],
                $setting
            );
        }
    }
}
