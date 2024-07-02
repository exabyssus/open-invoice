<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class InvoiceAmountOverview extends ChartWidget
{
    protected static ?string $heading = 'Rēķinu summas';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = Filament::getTenant()
            ->invoices
            ->groupBy(fn(Invoice $invoice) => $invoice->created_at->month)
            ->map(fn(Collection $month) => $month->sum(fn (Invoice $invoice) => (int)$invoice->getTotalWithVatAndTax()->getAmount() / 100));

        return [
            'datasets' => [
                [
                    'label' => __('resources.invoices.plural'),
                    'data' => $data->map(fn ($value, $key) => $value),
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
