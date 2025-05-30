<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CommissionStatsWidget;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\OrdersChartWidget;
use App\Filament\Widgets\OrderStatsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\RevenueStatsWidget;
use Filament\Pages\Dashboard as BasePage;
use Filament\Widgets\AccountWidget;

class Dashboard extends BasePage
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
            OrderStatsWidget::class,
            RevenueStatsWidget::class,
            CommissionStatsWidget::class,
        ];
    }

    protected function getWidgetColumnSpan(string $widget): int | array | string
    {
        // Make the AccountWidget full width
        if ($widget === AccountWidget::class) {
            return 'full';
        }

        // Default to 1 column for other widgets
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            OrdersChartWidget::class,
            RevenueChartWidget::class,
            LatestOrdersWidget::class,
        ];
    }
}
