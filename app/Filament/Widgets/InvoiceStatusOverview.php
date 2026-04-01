<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Support\Money;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatusOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        $unpaid = $tenant->invoices()->where('is_paid', false)->get();
        $outstandingAmount = $unpaid->sum(fn (Invoice $i) => (int) $i->getTotalWithVatAndTax()->getAmount());

        $monthlyData = collect(range(11, 0))->map(function ($monthsAgo) use ($tenant) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = now()->subMonths($monthsAgo)->endOfMonth();

            return $tenant->invoices()
                ->where('is_paid', true)
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->sum(fn (Invoice $i) => (int) $i->getTotalWithVatAndTax()->getAmount());
        });

        $incomeTotal = $monthlyData->sum();
        $chartData = $monthlyData->map(fn ($v) => $v / 100)->values()->toArray();

        return [
            Stat::make(__('pages.dashboard.income_12_months'), Money::defaultFormat(\Money\Money::EUR($incomeTotal)))
                ->description(__('pages.dashboard.paid_invoices'))
                ->chart($chartData)
                ->chartColor('success'),
            Stat::make(__('pages.dashboard.outstanding'), Money::defaultFormat(\Money\Money::EUR($outstandingAmount)))
                ->description(__('pages.dashboard.unpaid', ['count' => $unpaid->count()]))
                ->color('warning'),
        ];
    }
}
