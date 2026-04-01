<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\Money;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestInvoices extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();

        $clients = $tenant->clients->pluck('title', 'id');

        return $table
            ->query(
                Invoice::query()
                    ->where('company_id', $tenant->id)
                    ->latest()
                    ->limit(5)
            )
            ->heading('Rēķini')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('resources.invoice.invoice_number')),
                Tables\Columns\TextColumn::make('client_id')
                    ->formatStateUsing(fn ($state) => $clients[$state] ?? '')
                    ->label(__('resources.invoice.client')),
                Tables\Columns\IconColumn::make('is_paid')
                    ->alignCenter()
                    ->label(__('resources.invoice.is_paid')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('resources.invoice.amount_total'))
                    ->state(fn (Invoice $invoice): string => Money::defaultFormat($invoice->getTotalWithVatAndTax())),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('resources.invoice.date'))
                    ->formatStateUsing(fn ($state) => $state->format('Y.m.d')),
            ])
            ->headerActions([
                Actions\Action::make('viewAll')
                    ->label('Visi rēķini')
                    ->url(InvoiceResource::getUrl('index', ['tenant' => $tenant]))
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('gray'),
                Actions\Action::make('createInvoice')
                    ->label('Jauns rēķins')
                    ->url(InvoiceResource::getUrl('create', ['tenant' => $tenant]))
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
