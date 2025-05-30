<?php

namespace Database\Seeders;

use App\Models\AddonService;
use Illuminate\Database\Seeder;

class AddonServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addonServices = [
            // Fixed price addon services
            [
                'name' => 'Stain Protection Treatment',
                'description' => 'Applies a protective coating to prevent future stains from setting into the carpet fibers.',
                'is_per_square_foot' => false,
                'price' => 60.00,
                'is_active' => true,
            ],
            [
                'name' => 'Deodorizing Treatment',
                'description' => 'Deep cleaning treatment to eliminate odors from pets, smoke, or other sources.',
                'is_per_square_foot' => false,
                'price' => 45.00,
                'is_active' => true,
            ],
            [
                'name' => 'Anti-Allergen Treatment',
                'description' => 'Special treatment to reduce allergens like dust mites, pet dander, and pollen.',
                'is_per_square_foot' => false,
                'price' => 75.00,
                'is_active' => true,
            ],
            [
                'name' => 'Color Restoration',
                'description' => 'Treatment to restore faded carpet colors and improve appearance.',
                'is_per_square_foot' => false,
                'price' => 100.00,
                'is_active' => true,
            ],
            [
                'name' => 'Moth Protection',
                'description' => 'Treatment to protect natural fiber carpets from moth damage.',
                'is_per_square_foot' => false,
                'price' => 55.00,
                'is_active' => true,
            ],
            
            // Per square foot addon services
            [
                'name' => 'Deep Stain Removal',
                'description' => 'Intensive treatment for removing deep, set-in stains such as wine, coffee, or ink.',
                'is_per_square_foot' => true,
                'price' => 0.75,
                'is_active' => true,
            ],
            [
                'name' => 'Hot Water Extraction',
                'description' => 'Deep cleaning using hot water extraction method for heavily soiled areas.',
                'is_per_square_foot' => true,
                'price' => 0.50,
                'is_active' => true,
            ],
            [
                'name' => 'Sanitizing Treatment',
                'description' => 'Eliminates bacteria, viruses, and other microorganisms from carpet fibers.',
                'is_per_square_foot' => true,
                'price' => 0.60,
                'is_active' => true,
            ],
            [
                'name' => 'Pet Urine Treatment',
                'description' => 'Special enzymatic treatment to break down and remove pet urine and odors.',
                'is_per_square_foot' => true,
                'price' => 0.85,
                'is_active' => true,
            ],
            [
                'name' => 'Waterproofing Treatment',
                'description' => 'Applying a water-resistant coating to protect against spills and moisture.',
                'is_per_square_foot' => true,
                'price' => 0.95,
                'is_active' => true,
            ],
        ];

        $this->command->info('Creating addon services...');
        
        foreach ($addonServices as $serviceData) {
            AddonService::updateOrCreate(
                ['name' => $serviceData['name']],
                $serviceData
            );
            $this->command->info("Created addon service: {$serviceData['name']}");
        }
        
        $this->command->info('Addon services created successfully.');
    }
}
