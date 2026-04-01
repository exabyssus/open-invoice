<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\InvoiceAmountOverview;
use App\Filament\Widgets\InvoicesByClientChart;
use App\Filament\Widgets\InvoiceStatusOverview;
use App\Filament\Widgets\LatestInvoices;
use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    public function getColumns(): int|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            InvoiceStatusOverview::make(),
            InvoiceAmountOverview::make(),
            InvoicesByClientChart::make(),
            LatestInvoices::make(),
        ];
    }
}
