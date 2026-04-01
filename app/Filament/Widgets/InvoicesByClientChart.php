<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class InvoicesByClientChart extends ChartWidget
{
    protected ?string $heading = 'Invoices by Client';

    protected ?string $maxHeight = '200px';

    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last year',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $from = now()->subDays($days)->startOfDay();

        $tenant = Filament::getTenant();
        $clients = $tenant->clients->pluck('title', 'id');

        $invoices = $tenant
            ->invoices()
            ->where('created_at', '>=', $from)
            ->get();

        $grouped = $invoices->groupBy('client_id');

        $labels = [];
        $data = [];
        $colors = ['#06b6d4', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444', '#3b82f6', '#ec4899', '#14b8a6'];

        $i = 0;
        foreach ($grouped as $clientId => $clientInvoices) {
            $labels[] = $clients[$clientId] ?? 'Unknown';
            $data[] = $clientInvoices->sum(fn (Invoice $inv) => (int) $inv->getTotalWithVatAndTax()->getAmount()) / 100;
            $i++;
        }

        if (empty($data)) {
            $labels = ['No invoices'];
            $data = [0];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
