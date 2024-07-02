<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceAmountOverview;
use App\Filament\Widgets\InvoiceStatusOverview;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Widgets;

class Dashboard extends FilamentDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public function getWidgets(): array
    {
        return [
            InvoiceAmountOverview::make(),
            InvoiceStatusOverview::make(),
        ];
    }
}

