<?php

namespace App\Console\Commands;

use App\Models\Carpet;
use App\Models\CarpetType;
use Illuminate\Console\Command;

class MigrateCarpetTypesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-carpet-types-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from legacy type and dimensions fields to new carpet_type_id, width, and length fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of carpet data to new carpet type system...');
        
        // Make sure carpet types exist
        $carpetTypesCount = CarpetType::count();
        if ($carpetTypesCount === 0) {
            $this->error('No carpet types found in the database. Please run the CarpetTypeSeeder first.');
            return 1;
        }
        
        $this->info("Found {$carpetTypesCount} carpet types in the database.");
        
        // Get all carpets that need migration (those without carpet_type_id set)
        $carpets = Carpet::whereNull('carpet_type_id')->get();
        
        if ($carpets->isEmpty()) {
            $this->info('No carpets found that need migration. All carpet_type_id fields are already set.');
            return 0;
        }
        
        $this->info("Found {$carpets->count()} carpets that need migration.");
        
        // Create a map of legacy types to new carpet type IDs for quick lookup
        $typeMap = [
            'wool' => CarpetType::where('name', 'Specialty Wool')->first()?->id,
            'synthetic' => CarpetType::where('name', 'Standard Residential')->first()?->id,
            'silk' => CarpetType::where('name', 'Persian/Oriental Hand-Knotted')->first()?->id,
            'cotton' => CarpetType::where('name', 'Residential Berber')->first()?->id,
            'jute' => CarpetType::where('name', 'Commercial Low-Pile')->first()?->id,
            'shag' => CarpetType::where('name', 'Plush/Saxony')->first()?->id,
            'persian' => CarpetType::where('name', 'Persian/Oriental Hand-Knotted')->first()?->id,
            'oriental' => CarpetType::where('name', 'Oriental Rug (Small)')->first()?->id,
            'modern' => CarpetType::where('name', 'Standard Residential')->first()?->id,
            'other' => CarpetType::where('name', 'Standard Residential')->first()?->id,
        ];
        
        // Set a default carpet type for cases where the map fails
        $defaultCarpetTypeId = CarpetType::where('name', 'Standard Residential')->first()?->id;
        if (!$defaultCarpetTypeId) {
            $this->error('Could not find default carpet type (Standard Residential). Aborting migration.');
            return 1;
        }
        
        $bar = $this->output->createProgressBar($carpets->count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        foreach ($carpets as $carpet) {
            try {
                // Get dimensions from the legacy field
                $width = null;
                $length = null;
                
                if (!empty($carpet->dimensions) && is_array($carpet->dimensions)) {
                    $width = $carpet->dimensions['width'] ?? null;
                    $length = $carpet->dimensions['length'] ?? null;
                } elseif (is_string($carpet->dimensions)) {
                    // Try to parse dimensions from string like "2m x 3m"
                    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:m|ft)?\s*x\s*(\d+(?:\.\d+)?)\s*(?:m|ft)?/i', $carpet->dimensions, $matches)) {
                        $width = floatval($matches[1]);
                        $length = floatval($matches[2]);
                    }
                }
                
                // Default dimensions if parsing failed
                if (!$width || !$length) {
                    $width = 3.0; // Default width in feet
                    $length = 5.0; // Default length in feet
                }
                
                // Map the legacy type to a new carpet type ID
                $carpetTypeId = $typeMap[$carpet->type] ?? $defaultCarpetTypeId;
                
                // Update the carpet with new fields
                $carpet->update([
                    'carpet_type_id' => $carpetTypeId,
                    'width' => $width,
                    'length' => $length,
                ]);
                
                $updated++;
            } catch (\Exception $e) {
                $this->error("Error migrating carpet ID {$carpet->id}: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Migration completed: {$updated} carpets updated successfully, {$errors} errors encountered.");
        
        if ($updated > 0) {
            $this->info('Now you can safely run the migration to remove the legacy fields.');
        }
        
        return 0;
    }
}
