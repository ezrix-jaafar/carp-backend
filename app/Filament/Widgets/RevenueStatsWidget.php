<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Calculate revenue stats
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->sum('amount');
        
        // This month's revenue
        $thisMonthRevenue = Payment::where('status', 'completed')
            ->where('paid_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');
        
        // Last month's revenue
        $lastMonthRevenue = Payment::where('status', 'completed')
            ->where('paid_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->where('paid_at', '<', Carbon::now()->startOfMonth())
            ->sum('amount');
        
        // Calculate change percentage
        $changePercentage = $lastMonthRevenue > 0 
            ? round(($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100) 
            : 0;
            
        $changeDirection = $changePercentage >= 0 ? 'up' : 'down';
            
        return [
            Stat::make('Total Revenue', 'RM ' . number_format($totalRevenue, 2))
                ->description('All time')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('This Month', 'RM ' . number_format($thisMonthRevenue, 2))
                ->description($changePercentage . '% ' . $changeDirection . ' from last month')
                ->descriptionIcon($changeDirection === 'up' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($changeDirection === 'up' ? 'success' : 'danger'),
                
            Stat::make('Pending Payments', 'RM ' . number_format($pendingPayments, 2))
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
