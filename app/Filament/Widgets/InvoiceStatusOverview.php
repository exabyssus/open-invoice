<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Support\Money;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class InvoiceStatusOverview extends Widget
{
    protected string $view = 'filament.widgets.invoice-status-overview';

    protected int|string|array $columnSpan = 1;

    public function getOutstandingAmount(): string
    {
        $unpaid = Filament::getTenant()
            ->invoices()
            ->where('is_paid', false)
            ->get();

        $amount = $unpaid->sum(fn (Invoice $i) => (int) $i->getTotalWithVatAndTax()->getAmount());

        return Money::defaultFormat(\Money\Money::EUR($amount));
    }

    public function getUnpaidCount(): int
    {
        return Filament::getTenant()
            ->invoices()
            ->where('is_paid', false)
            ->count();
    }
}
