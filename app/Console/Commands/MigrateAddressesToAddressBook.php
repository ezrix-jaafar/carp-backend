<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateAddressesToAddressBook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy addresses from clients and orders to the new address book system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of addresses to the new address book system...');
        
        DB::beginTransaction();
        try {
            // Step 1: Migrate client addresses
            $this->migrateClientAddresses();
            
            // Step 2: Migrate order pickup and delivery addresses
            $this->migrateOrderAddresses();
            
            DB::commit();
            $this->info('Address migration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Address migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Migrate client addresses to the address book.
     */
    private function migrateClientAddresses()
    {
        $this->info('Migrating client addresses...');
        $clientCount = 0;
        $clientAddressCount = 0;
        
        $clients = Client::all();
        foreach ($clients as $client) {
            $clientCount++;
            
            // Skip clients without addresses
            if (empty($client->address)) {
                continue;
            }
            
            // Create a new address record for the client's primary address
            $address = new Address([
                'client_id' => $client->id,
                'label' => 'Home Address',
                'address_line_1' => $client->address,
                'city' => $client->city ?? '',
                'state' => $client->state ?? '',
                'postal_code' => '', // No postal code in legacy data, leave empty
                'is_default' => true,
            ]);
            
            $address->save();
            $clientAddressCount++;
            
            if ($clientCount % 10 == 0) {
                $this->info("Processed {$clientCount} clients, created {$clientAddressCount} addresses");
            }
        }
        
        $this->info("Completed client address migration. Processed {$clientCount} clients, created {$clientAddressCount} addresses");
    }
    
    /**
     * Migrate order pickup and delivery addresses to the address book.
     */
    private function migrateOrderAddresses()
    {
        $this->info('Migrating order pickup and delivery addresses...');
        $orderCount = 0;
        $pickupAddressCount = 0;
        $deliveryAddressCount = 0;
        
        $orders = Order::all();
        foreach ($orders as $order) {
            $orderCount++;
            $client = $order->client;
            
            if (!$client) {
                $this->warn("Order #{$order->id} has no client, skipping address migration");
                continue;
            }
            
            // Migrate pickup address if not empty
            if (!empty($order->pickup_address)) {
                // Check if this exact address already exists for this client
                $existingAddress = Address::where('client_id', $client->id)
                    ->where('address_line_1', $order->pickup_address)
                    ->first();
                
                if ($existingAddress) {
                    // Use existing address
                    $order->pickup_address_id = $existingAddress->id;
                } else {
                    // Create a new address
                    $address = new Address([
                        'client_id' => $client->id,
                        'label' => 'Pickup Address',
                        'address_line_1' => $order->pickup_address,
                        'city' => $client->city ?? '',
                        'state' => $client->state ?? '',
                        'postal_code' => '',
                        'is_default' => false,
                    ]);
                    
                    $address->save();
                    $order->pickup_address_id = $address->id;
                    $pickupAddressCount++;
                }
            }
            
            // Migrate delivery address if not empty
            if (!empty($order->delivery_address)) {
                // Check if this exact address already exists for this client
                $existingAddress = Address::where('client_id', $client->id)
                    ->where('address_line_1', $order->delivery_address)
                    ->first();
                
                if ($existingAddress) {
                    // Use existing address
                    $order->delivery_address_id = $existingAddress->id;
                } else {
                    // Create a new address
                    $address = new Address([
                        'client_id' => $client->id,
                        'label' => 'Delivery Address',
                        'address_line_1' => $order->delivery_address,
                        'city' => $client->city ?? '',
                        'state' => $client->state ?? '',
                        'postal_code' => '',
                        'is_default' => false,
                    ]);
                    
                    $address->save();
                    $order->delivery_address_id = $address->id;
                    $deliveryAddressCount++;
                }
            }
            
            // Save the order with new address references
            $order->save();
            
            if ($orderCount % 10 == 0) {
                $this->info("Processed {$orderCount} orders, created {$pickupAddressCount} pickup addresses and {$deliveryAddressCount} delivery addresses");
            }
        }
        
        $this->info("Completed order address migration. Processed {$orderCount} orders, created {$pickupAddressCount} pickup addresses and {$deliveryAddressCount} delivery addresses");
    }
}
