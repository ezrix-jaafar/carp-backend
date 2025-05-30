<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create HQ Users if they don't exist
        $this->createUserIfNotExists([
            'name' => 'HQ Admin',
            'email' => 'admin@carpetclean.com',
            'password' => Hash::make('password'),
            'role' => 'hq',
        ]);
        
        // Create Staff Users if they don't exist
        $this->createUserIfNotExists([
            'name' => 'Staff User',
            'email' => 'staff@carpetclean.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
        
        // Create Agent Users (we'll attach agent details in AgentSeeder)
        for ($i = 1; $i <= 5; $i++) {
            $this->createUserIfNotExists([
                'name' => "Agent User {$i}",
                'email' => "agent{$i}@carpetclean.com",
                'password' => Hash::make('password'),
                'role' => 'agent',
            ]);
        }
        
        // Create Client Users (we'll attach client details in ClientSeeder)
        for ($i = 1; $i <= 10; $i++) {
            $this->createUserIfNotExists([
                'name' => "Client User {$i}",
                'email' => "client{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'client',
            ]);
        }
    }
    
    /**
     * Create a user if one with the same email doesn't already exist.
     *
     * @param array $userData
     * @return User
     */
    private function createUserIfNotExists(array $userData): User
    {
        $user = User::firstOrNew(['email' => $userData['email']]);
        
        // If the user is new (doesn't exist yet), fill and save
        if (!$user->exists) {
            $user->fill($userData);
            $user->save();
            $this->command->info("Created user: {$userData['email']}");
        } else {
            $this->command->info("User already exists: {$userData['email']}");
            
            // Update role if not set (for existing users without roles)
            if (empty($user->role) && isset($userData['role'])) {
                $user->role = $userData['role'];
                $user->save();
                $this->command->info("Updated role for: {$userData['email']}");
            }
        }
        
        return $user;
    }
}
