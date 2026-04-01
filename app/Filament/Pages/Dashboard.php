<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\InvoicesByClientChart;
use App\Filament\Widgets\InvoiceStatusOverview;
use App\Filament\Widgets\LatestInvoices;
use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            InvoiceStatusOverview::make(),
            InvoicesByClientChart::make(),
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            LatestInvoices::make(),
        ];
    }
}
