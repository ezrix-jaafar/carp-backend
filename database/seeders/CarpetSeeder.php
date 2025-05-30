<?php

namespace Database\Seeders;

use App\Models\Carpet;
use App\Models\CarpetType;
use App\Models\Order;
use Illuminate\Database\Seeder;

class CarpetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all orders that have carpets specified but no actual carpet records yet
        $orders = Order::where('total_carpets', '>', 0)
            ->whereDoesntHave('carpets')
            ->get();
        
        if ($orders->isEmpty()) {
            $this->command->info('No orders without carpets found. All orders have carpets already.');
            return;
        }
        
        $this->command->info("Found {$orders->count()} orders without carpet records. Creating carpets now.");

        if ($orders->isEmpty()) {
            $this->command->info('No orders with carpets found. Please run the OrderSeeder first.');
            return;
        }

        // Fetch all carpet types from the database
        $carpetTypes = CarpetType::all();
        if ($carpetTypes->isEmpty()) {
            $this->command->error('No carpet types found. Please run the CarpetTypeSeeder first.');
            return;
        }

        // Carpet colors
        $carpetColors = ['Red', 'Blue', 'Green', 'Brown', 'Beige', 'Gold', 'Black', 'White', 'Multicolor'];

        // Track used QR codes
        $usedQrCodes = [];

        // Create carpets for each order
        foreach ($orders as $order) {
            for ($i = 1; $i <= $order->total_carpets; $i++) {
                // Random dimensions (feet)
                $width = rand(15, 80) / 10; // 1.5ft to 8.0ft
                $length = rand(20, 120) / 10; // 2.0ft to 12.0ft

                // Additional charges (5% chance)
                $additionalCharges = (rand(0, 20) === 0) ? rand(10, 50) : 0;

                // Unique QR code
                $qrCode = $this->generateUniqueQrCode($order->id, $i, $usedQrCodes);
                $usedQrCodes[] = $qrCode;

                // Randomly select a carpet type
                $carpetType = $carpetTypes->random();

                // Create the carpet
                Carpet::create([
                    'order_id' => $order->id,
                    'carpet_type_id' => $carpetType->id,
                    'qr_code' => $qrCode,
                    'width' => $width,
                    'length' => $length,
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
     * Generate a unique QR code.
     */
    private function generateUniqueQrCode(int $orderId, int $sequence, array $usedQrCodes): string
    {
        do {
            $timestamp = now()->format('YmdHis');
            $randomStr = substr(md5(rand()), 0, 5);
            $qrCode = "CARP-{$orderId}-{$sequence}-{$timestamp}-{$randomStr}";
        } while (in_array($qrCode, $usedQrCodes) || Carpet::where('qr_code', $qrCode)->exists());

        return $qrCode;
    }

    /**
     * Map order status to carpet status.
     */
    private function getCarpetStatusFromOrderStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'pending', 'assigned' => 'pending',
            'picked_up' => 'picked_up',
            'in_cleaning' => 'in_cleaning',
            'cleaned' => 'cleaned',
            'delivered', 'completed' => 'delivered',
            'cancelled' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Get random notes or null.
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