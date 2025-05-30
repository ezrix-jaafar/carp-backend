<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class OrderStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Calculate order stats
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $pickupOrders = Order::where('status', 'picked_up')->count();
        
        // Get change percentage compared to last month
        $lastMonthOrders = Order::where('created_at', '>=', Carbon::now()->subMonth())
            ->where('created_at', '<', Carbon::now())
            ->count();
        
        $previousMonthOrders = Order::where('created_at', '>=', Carbon::now()->subMonths(2))
            ->where('created_at', '<', Carbon::now()->subMonth())
            ->count();
        
        $changePercentage = $previousMonthOrders > 0 
            ? round(($lastMonthOrders - $previousMonthOrders) / $previousMonthOrders * 100) 
            : 0;
            
        $changeDirection = $changePercentage >= 0 ? 'up' : 'down';
            
        return [
            Stat::make('Total Orders', $totalOrders)
                ->description('Lifetime orders')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
            
            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting assignment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Orders in Collection', $pickupOrders)
                ->description($changePercentage . '% ' . $changeDirection . ' from last month')
                ->descriptionIcon($changeDirection === 'up' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($changeDirection === 'up' ? 'success' : 'danger'),
        ];
    }
}
