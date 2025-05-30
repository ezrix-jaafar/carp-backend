<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseWidget;

class CustomAccountWidget extends BaseWidget
{
    // Make the widget take up the full width
    protected int | string | array $columnSpan = 'full';
}
