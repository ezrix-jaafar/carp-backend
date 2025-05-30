<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find all users with role 'agent'
        $agentUsers = User::where('role', 'agent')->get();
        
        // Each agent will have different commission rates
        $commissionData = [
            // Fixed commission (RM), percentage commission (%)
            [50.00, 5.0],  // Agent 1: RM50 fixed + 5% of invoice
            [30.00, 7.5],  // Agent 2: RM30 fixed + 7.5% of invoice
            [45.00, 5.5],  // Agent 3: RM45 fixed + 5.5% of invoice
            [40.00, 6.0],  // Agent 4: RM40 fixed + 6% of invoice
            [25.00, 8.0],  // Agent 5: RM25 fixed + 8% of invoice
        ];
        
        // Create agent records for each agent user
        foreach ($agentUsers as $index => $user) {
            $commissionIndex = $index % count($commissionData);
            Agent::create([
                'user_id' => $user->id,
                'fixed_commission' => $commissionData[$commissionIndex][0],
                'percentage_commission' => $commissionData[$commissionIndex][1],
                'status' => 'active',
                'notes' => "Sample agent with fixed commission RM{$commissionData[$commissionIndex][0]} + {$commissionData[$commissionIndex][1]}%"
            ]);
        }
    }
}
