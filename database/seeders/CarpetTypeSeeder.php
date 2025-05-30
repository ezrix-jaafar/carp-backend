<?php

namespace Database\Seeders;

use App\Models\CarpetType;
use Illuminate\Database\Seeder;

class CarpetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carpetTypes = [
            // Fixed price carpet types
            [
                'name' => 'Standard Residential',
                'description' => 'Standard wall-to-wall residential carpet. Most common in homes and apartments.',
                'is_per_square_foot' => false,
                'price' => 150.00,
                'cleaning_instructions' => 'Standard hot water extraction with pre-treatment for spots.',
            ],
            [
                'name' => 'Premium Area Rug (Small)',
                'description' => 'Premium area rug, typically 5x7 feet or smaller.',
                'is_per_square_foot' => false,
                'price' => 80.00,
                'cleaning_instructions' => 'Hand wash with specialized area rug techniques. No harsh chemicals.',
            ],
            [
                'name' => 'Premium Area Rug (Medium)',
                'description' => 'Premium area rug, typically 6x9 to 8x10 feet.',
                'is_per_square_foot' => false,
                'price' => 120.00,
                'cleaning_instructions' => 'Hand wash with specialized area rug techniques. No harsh chemicals.',
            ],
            [
                'name' => 'Premium Area Rug (Large)',
                'description' => 'Premium area rug, typically 9x12 feet or larger.',
                'is_per_square_foot' => false,
                'price' => 180.00,
                'cleaning_instructions' => 'Hand wash with specialized area rug techniques. No harsh chemicals.',
            ],
            [
                'name' => 'Oriental Rug (Small)',
                'description' => 'Delicate oriental rugs requiring special care, typically 5x7 feet or smaller.',
                'is_per_square_foot' => false,
                'price' => 120.00,
                'cleaning_instructions' => 'Special gentle cleaning techniques. Hand-washed only with proper pH-balanced solutions.',
            ],
            
            // Per square foot pricing carpet types
            [
                'name' => 'Commercial Low-Pile',
                'description' => 'Low-pile carpet typically found in offices and commercial spaces.',
                'is_per_square_foot' => true,
                'price' => 0.75,
                'cleaning_instructions' => 'Standard commercial cleaning with fast-drying techniques.',
            ],
            [
                'name' => 'Residential Berber',
                'description' => 'Loop-pile Berber carpet commonly used in homes.',
                'is_per_square_foot' => true,
                'price' => 0.85,
                'cleaning_instructions' => 'Low-moisture cleaning to prevent shrinking and distortion of loops.',
            ],
            [
                'name' => 'Plush/Saxony',
                'description' => 'Soft, luxurious carpet with higher pile, common in bedrooms and living areas.',
                'is_per_square_foot' => true,
                'price' => 0.95,
                'cleaning_instructions' => 'Deep extraction cleaning with longer drying time due to pile density.',
            ],
            [
                'name' => 'Specialty Wool',
                'description' => 'Natural wool carpets requiring special treatment.',
                'is_per_square_foot' => true,
                'price' => 1.50,
                'cleaning_instructions' => 'Low-moisture wool-safe cleaning solution. Avoid high alkalinity cleaners.',
            ],
            [
                'name' => 'Persian/Oriental Hand-Knotted',
                'description' => 'Valuable hand-knotted rugs requiring expert handling.',
                'is_per_square_foot' => true,
                'price' => 3.00,
                'cleaning_instructions' => 'Expert hand washing only with specialized dye-stable solutions and controlled drying.',
            ],
        ];

        $this->command->info('Creating carpet types...');
        
        foreach ($carpetTypes as $typeData) {
            CarpetType::updateOrCreate(
                ['name' => $typeData['name']],
                $typeData
            );
            $this->command->info("Created carpet type: {$typeData['name']}");
        }
        
        $this->command->info('Carpet types created successfully.');
    }
}
