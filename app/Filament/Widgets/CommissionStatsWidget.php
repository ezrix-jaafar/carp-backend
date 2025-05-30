<?php

namespace App\Filament\Widgets;

use App\Models\Commission;
use App\Models\Agent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class CommissionStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Calculate commission stats
        $totalCommissions = Commission::sum('total_commission');
        $pendingCommissions = Commission::whereNull('paid_at')->sum('total_commission');
        $activeAgents = Agent::where('status', 'active')->count();
        
        // This month's commissions
        $thisMonthCommissions = Commission::where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('total_commission');
        
        // Calculate average commission per agent
        $averageCommissionPerAgent = $activeAgents > 0 
            ? round($totalCommissions / $activeAgents, 2) 
            : 0;
            
        return [
            Stat::make('Total Commissions', 'RM ' . number_format($totalCommissions, 2))
                ->description('All time')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Pending Payouts', 'RM ' . number_format($pendingCommissions, 2))
                ->description('Awaiting payment to agents')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Avg Commission/Agent', 'RM ' . number_format($averageCommissionPerAgent, 2))
                ->description('Based on ' . $activeAgents . ' active agents')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
