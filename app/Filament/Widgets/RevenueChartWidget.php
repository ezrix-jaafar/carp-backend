<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue';
    protected static ?int $sort = 5;
    
    protected function getData(): array
    {
        $data = Payment::select(
                DB::raw('YEAR(paid_at) as year'),
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('paid_at', '>=', Carbon::now()->startOfYear())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
            
        $months = [];
        $revenue = [];
        
        foreach ($data as $row) {
            $monthName = Carbon::createFromDate($row->year, $row->month, 1)->format('M Y');
            $months[] = $monthName;
            $revenue[] = round($row->total, 2);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Revenue (RM)',
                    'data' => $revenue,
                    'backgroundColor' => '#4ade80',
                    'borderColor' => '#4ade80',
                ],
            ],
            'labels' => $months,
        ];
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
}
