<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with the 'client' role that don't already have a client record
        $clientUsers = User::where('role', 'client')
            ->whereDoesntHave('client')
            ->get();
            
        foreach ($clientUsers as $user) {
            Client::create([
                'user_id' => $user->id,
                'phone' => '01' . rand(1, 9) . rand(10000000, 99999999), // Malaysian phone format
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement([
                    'Kuala Lumpur', 'Selangor', 'Penang', 'Johor', 'Perak', 'Negeri Sembilan', 
                    'Kedah', 'Pahang', 'Kelantan', 'Terengganu', 'Malacca', 'Perlis', 'Sabah', 'Sarawak'
                ]),
            ]);
        }
        
        // Create a few additional clients if we need more
        if ($clientUsers->count() < 5) {
            $neededClients = 5 - $clientUsers->count();
            $currentClientCount = $clientUsers->count();
            
            for ($i = 0; $i < $neededClients; $i++) {
                $clientNumber = $currentClientCount + $i + 1;
                $email = 'client' . $clientNumber . '@example.com';
                
                // Check if a user with this email exists
                $existingUser = User::where('email', $email)->first();
                
                if ($existingUser) {
                    $this->command->info("User already exists: {$email}");
                    
                    // If the user exists but doesn't have a client record, create one
                    if (!$existingUser->client) {
                        Client::create([
                            'user_id' => $existingUser->id,
                            'phone' => '01' . rand(1, 9) . rand(10000000, 99999999),
                            'address' => fake()->streetAddress(),
                            'city' => fake()->city(),
                            'state' => fake()->randomElement([
                                'Kuala Lumpur', 'Selangor', 'Penang', 'Johor', 'Perak', 'Negeri Sembilan', 
                                'Kedah', 'Pahang', 'Kelantan', 'Terengganu', 'Malacca', 'Perlis', 'Sabah', 'Sarawak'
                            ]),
                        ]);
                        
                        $this->command->info("Created client record for existing user: {$email}");
                    } else {
                        $this->command->info("Client record already exists for: {$email}");
                    }
                } else {
                    // Create a new user with client role
                    $user = User::create([
                        'role' => 'client',
                        'name' => fake()->name(),
                        'email' => $email,
                        'password' => bcrypt('password'),
                    ]);
                    
                    // Create a client record for the new user
                    Client::create([
                        'user_id' => $user->id,
                        'phone' => '01' . rand(1, 9) . rand(10000000, 99999999),
                        'address' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'state' => fake()->randomElement([
                            'Kuala Lumpur', 'Selangor', 'Penang', 'Johor', 'Perak', 'Negeri Sembilan', 
                            'Kedah', 'Pahang', 'Kelantan', 'Terengganu', 'Malacca', 'Perlis', 'Sabah', 'Sarawak'
                        ]),
                    ]);
                    
                    $this->command->info("Created new client: {$email}");
                }
            }
        }
    }
}
