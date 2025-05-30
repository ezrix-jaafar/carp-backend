<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Client;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all clients and agents
        $clients = Client::all();
        $agents = Agent::all();
        
        if ($clients->isEmpty() || $agents->isEmpty()) {
            $this->command->info('No clients or agents found. Please run the ClientSeeder and AgentSeeder first.');
            return;
        }
        
        // Different statuses for orders that match the enum in migration
        $orderStatuses = ['pending', 'assigned', 'picked_up', 'in_cleaning', 'cleaned', 'delivered', 'completed', 'cancelled'];
        
        // Check if we already have orders in the system
        $existingOrderCount = Order::count();
        if ($existingOrderCount > 0) {
            // Count orders with invoice-eligible statuses (cleaned, delivered, completed)
            $eligibleOrderCount = Order::whereIn('status', ['cleaned', 'delivered', 'completed'])->count();
            
            if ($eligibleOrderCount >= 3) {
                $this->command->info("Found {$existingOrderCount} existing orders with {$eligibleOrderCount} eligible for invoicing. Skipping order creation.");
                return;
            } else {
                $this->command->info("Found {$existingOrderCount} existing orders but only {$eligibleOrderCount} eligible for invoicing. Creating additional test orders.");
                // Continue execution to create additional orders
            }
        }
        
        // Get the highest existing reference number to avoid duplicates
        $latestOrder = Order::orderBy('id', 'desc')->first();
        $referenceCounter = 1;
        
        if ($latestOrder) {
            // Extract the sequence number from the reference number (ORD-20250428-001)
            if (preg_match('/-(\d{3})$/', $latestOrder->reference_number, $matches)) {
                $referenceCounter = (int)$matches[1] + 1;
            }
        }
        
        $this->command->info("Starting from reference counter: {$referenceCounter}");
        
        // Create a few orders with different statuses for each client
        foreach ($clients as $client) {
            // Ensure we create at least one order with a status eligible for invoicing
            // Each client will have 1-3 orders
            $numOrders = rand(1, 3);
            
            for ($i = 0; $i < $numOrders; $i++) {
                // Select a random agent for this order
                $agent = $agents->random();
                
                // Ensure we have some orders with completed statuses for invoices
                // For the first order for each client, use an invoice-eligible status
                if ($i === 0 && $existingOrderCount < 5) {
                    $status = $orderStatuses[array_rand(array_slice($orderStatuses, 4, 3))]; // Use cleaned, delivered, or completed
                } else {
                    // Randomly select a status for this order
                    $status = $orderStatuses[array_rand($orderStatuses)];
                }
                
                // Generate random dates for pickup and delivery
                $pickupDate = Carbon::now()->subDays(rand(1, 30));
                $deliveryDate = Carbon::parse($pickupDate)->addDays(rand(3, 10));
                
                // Generate a random number of carpets (1-5)
                $totalCarpets = rand(1, 5);
                
                // Create reference number manually to avoid duplicates
                $prefix = 'ORD';
                $date = now()->format('Ymd');
                $refNumber = $prefix . '-' . $date . '-' . str_pad($referenceCounter, 3, '0', STR_PAD_LEFT);
                $referenceCounter++;
                
                // Create the order
                $order = Order::create([
                    'client_id' => $client->id,
                    'agent_id' => $agent->id,
                    'status' => $status,
                    'pickup_date' => $pickupDate,
                    'pickup_address' => $client->address,
                    'delivery_date' => $deliveryDate,
                    'delivery_address' => $client->address,
                    'notes' => $this->getRandomNotes(),
                    'total_carpets' => $totalCarpets,
                    'reference_number' => $refNumber,
                ]);
                
                $this->command->info("Created order {$refNumber} for client {$client->user->email}");
            }
        }
    }
    
    /**
     * Get random notes for an order.
     *
     * @return string|null
     */
    private function getRandomNotes(): ?string
    {
        $notes = [
            'Please handle with care.',
            'The carpet has some stains that need special attention.',
            'The carpet is very old and fragile.',
            'There are pet stains on the carpet.',
            'The carpet is a family heirloom.',
            'Please use eco-friendly cleaning products.',
            null,
            null,
        ];
        
        return $notes[array_rand($notes)];
    }
}
