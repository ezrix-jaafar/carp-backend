<?php

namespace Database\Seeders;

use App\Models\Carpet;
use App\Models\Order;
use Illuminate\Database\Seeder;

class CarpetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we already have carpets in the system
        $existingCarpetCount = Carpet::count();
        if ($existingCarpetCount > 0) {
            $this->command->info("Found {$existingCarpetCount} existing carpets. Skipping carpet creation.");
            return;
        }
        
        // Get all orders that have carpets specified
        $orders = Order::where('total_carpets', '>', 0)->get();
        
        if ($orders->isEmpty()) {
            $this->command->info('No orders with carpets found. Please run the OrderSeeder first.');
            return;
        }
        
        // Possible carpet types - must match enum values in migration
        $carpetTypes = ['wool', 'synthetic', 'silk', 'cotton', 'other'];
        
        // Possible carpet colors
        $carpetColors = ['Red', 'Blue', 'Green', 'Brown', 'Beige', 'Gold', 'Black', 'White', 'Multicolor'];
        
        // Track QR codes to avoid duplicates (shouldn't happen, but just to be safe)
        $usedQrCodes = [];
        
        // Create carpets for each order
        foreach ($orders as $order) {
            // Generate the number of carpets specified in the order
            for ($i = 1; $i <= $order->total_carpets; $i++) {
                // Generate random dimensions (in meters)
                $width = rand(10, 50) / 10; // 1.0m to 5.0m
                $length = rand(10, 70) / 10; // 1.0m to 7.0m
                
                // Random additional charges
                $additionalCharges = (rand(0, 20) === 0) ? rand(10, 50) : 0; // 5% chance of additional charges
                
                // Generate a unique QR code for this carpet
                $qrCode = $this->generateUniqueQrCode($order->id, $i, $usedQrCodes);
                $usedQrCodes[] = $qrCode;
                
                // Create the carpet
                $carpet = Carpet::create([
                    'order_id' => $order->id,
                    'qr_code' => $qrCode,
                    'type' => $carpetTypes[array_rand($carpetTypes)],
                    'dimensions' => [
                        'width' => $width,
                        'length' => $length,
                        'area' => round($width * $length, 2),
                    ],
                    'color' => $carpetColors[array_rand($carpetColors)],
                    'status' => $this->getCarpetStatusFromOrderStatus($order->status),
                    'notes' => $this->getRandomNotes(),
                    'additional_charges' => $additionalCharges,
                ]);
                
                $this->command->info("Created carpet {$qrCode} for order {$order->reference_number}");
            }
        }
    }
    
    /**
     * Generate a unique QR code that doesn't conflict with existing codes.
     *
     * @param int $orderId
     * @param int $sequence
     * @param array $usedQrCodes
     * @return string
     */
    private function generateUniqueQrCode(int $orderId, int $sequence, array $usedQrCodes): string
    {
        $timestamp = now()->format('YmdHis');
        $randomStr = substr(md5(rand()), 0, 5);
        $qrCode = "CARP-{$orderId}-{$sequence}-{$timestamp}-{$randomStr}";
        
        // If by some chance this QR code already exists, make it even more unique
        if (in_array($qrCode, $usedQrCodes) || Carpet::where('qr_code', $qrCode)->exists()) {
            return $this->generateUniqueQrCode($orderId, $sequence, $usedQrCodes);
        }
        
        return $qrCode;
    }
    
    /**
     * Determine carpet status based on order status.
     *
     * @param string $orderStatus
     * @return string
     */
    private function getCarpetStatusFromOrderStatus(string $orderStatus): string
    {
        switch ($orderStatus) {
            case 'pending':
            case 'assigned':
                return 'pending';
            case 'picked_up':
                return 'picked_up';
            case 'in_cleaning':
                return 'in_cleaning';
            case 'cleaned':
                return 'cleaned';
            case 'delivered':
            case 'completed':
                return 'delivered';
            case 'cancelled':
                return 'pending';
            default:
                return 'pending';
        }
    }
    
    /**
     * Get random notes for a carpet.
     *
     * @return string|null
     */
    private function getRandomNotes(): ?string
    {
        $notes = [
            'Heavy stains on one corner.',
            'Slight discoloration in the center.',
            'Frayed edges need repair.',
            'Small tear on the edge.',
            'Water damage on one side.',
            'Requires special cleaning method.',
            null,
            null,
            null,
        ];
        
        return $notes[array_rand($notes)];
    }
}
