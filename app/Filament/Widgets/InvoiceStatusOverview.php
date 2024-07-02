<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class InvoiceStatusOverview extends ChartWidget
{
    protected static ?string $heading = 'Apmaksāti';
    protected static ?string $maxHeight = '255px';

    protected function getData(): array
    {
        $unpaid = Filament::getTenant()->invoices()->where('is_paid', 0)->count();
        $paid = Filament::getTenant()->invoices()->where('is_paid', 1)->count();

        return [
            'datasets' => [
                [
                    'label' => __('resources.invoices.plural'),
                    'data' => [
                        $paid,
                        $unpaid,
                    ],
                ],
            ],
            'labels' => [
                'Paid',
                'Unpaid',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
