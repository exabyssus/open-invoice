<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class InvoiceAmountOverview extends ChartWidget
{
    protected ?string $heading = 'Invoiced Amount';

    protected ?string $maxHeight = '200px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $months = collect(range(1, 12));

        $invoices = Filament::getTenant()
            ->invoices()
            ->whereYear('created_at', now()->year)
            ->get();

        $paidData = $months->map(fn ($month) => $invoices
            ->filter(fn (Invoice $i) => $i->created_at->month === $month && $i->is_paid)
            ->sum(fn (Invoice $i) => (int) $i->getTotalWithVatAndTax()->getAmount()) / 100
        );

        $unpaidData = $months->map(fn ($month) => $invoices
            ->filter(fn (Invoice $i) => $i->created_at->month === $month && ! $i->is_paid)
            ->sum(fn (Invoice $i) => (int) $i->getTotalWithVatAndTax()->getAmount()) / 100
        );

        $labels = $months->map(fn ($m) => now()->setMonth($m)->translatedFormat('M'));

        return [
            'datasets' => [
                [
                    'label' => 'Paid',
                    'data' => $paidData->values(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Unpaid',
                    'data' => $unpaidData->values(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels->values(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
