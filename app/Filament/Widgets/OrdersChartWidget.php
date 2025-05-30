<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrdersChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Orders Trend';
    protected static ?int $sort = 4;
    
    protected function getData(): array
    {
        $data = Order::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $labels = $data->pluck('date')->toArray();
        $values = $data->pluck('count')->toArray();
        
        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $values,
                    'backgroundColor' => '#36a2eb',
                    'borderColor' => '#36a2eb',
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
