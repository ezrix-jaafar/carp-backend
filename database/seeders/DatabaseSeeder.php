<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call our custom seeders in the appropriate order
        $this->call([
            UserSeeder::class,         // Create users with different roles (HQ, staff, agents, clients)
            AgentSeeder::class,        // Create agent records linked to agent users
            ClientSeeder::class,       // Create client records linked to client users
            OrderSeeder::class,        // Create sample orders with carpets, invoices
            CarpetTypeSeeder::class,   // Create carpet types with pricing models
            AddonServiceSeeder::class, // Create addon services with pricing models
            CommissionTypeSeeder::class, // Create commission types with different structures
            TaxSettingSeeder::class,   // Create tax settings
            CarpetSeeder::class,       // Create carpets for orders
            InvoiceSeeder::class,      // Create invoices for orders
            PaymentSeeder::class,      // Create some sample payments for invoices
            CommissionSeeder::class,   // Create some sample commissions for completed payments
        ]);
    }
}
