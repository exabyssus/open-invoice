<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();

        return [
            Actions\Action::make('view')
                ->icon('heroicon-m-magnifying-glass')
                ->label(__('pages.invoices.view'))
                ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                    'tenant' => $tenant,
                    'record' => $record,
                    'method' => 'inline',
                ]))
                ->openUrlInNewTab(),
            Actions\Action::make('download')
                ->icon('heroicon-m-arrow-down-tray')
                ->label(__('pages.invoices.download'))
                ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                    'tenant' => $tenant,
                    'record' => $record,
                    'method' => 'download',
                ]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getHeading(): string|Htmlable
    {
        return __('pages.action_edit', ['name' => $this->getRecord()->invoice_number]);
    }
}
