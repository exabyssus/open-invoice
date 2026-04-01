<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class InvoicesByClientChart extends ChartWidget
{
    protected ?string $maxHeight = '200px';

    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return __('pages.dashboard.invoices_by_client');
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => __('pages.filters.last_7_days'),
            '30' => __('pages.filters.last_30_days'),
            '90' => __('pages.filters.last_90_days'),
            '365' => __('pages.filters.last_year'),
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

        foreach ($grouped as $clientId => $clientInvoices) {
            $labels[] = $clients[$clientId] ?? __('pages.dashboard.unknown');
            $data[] = $clientInvoices->sum(fn (Invoice $inv) => (int) $inv->getTotalWithVatAndTax()->getAmount()) / 100;
        }

        if (empty($data)) {
            $labels = [__('pages.dashboard.no_invoices')];
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
