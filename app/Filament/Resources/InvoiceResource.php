<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateInvoice;
use App\Filament\Pages\EditInvoice;
use App\Filament\Pages\ListInvoices;
use App\Filament\Pages\ViewInvoicePdf;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\GenerateInvoiceNumber;
use App\Support\Money;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $label = '';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getRelations(): array
    {
        return [
            \App\Filament\InvoiceLinesRelationManager::class
        ];
    }

    public static function form(Form $form): Form
    {
        $numberGenerator = new GenerateInvoiceNumber();
        return $form
            ->schema([
                TextInput::make('invoice_number')
                    ->label(__('resources.invoice.invoice_number'))
                    ->default($numberGenerator(Filament::getTenant()))
                    ->required(),
                Select::make('client_id')
                    ->label(__('resources.invoice.client'))
                    ->options(Client::query()->get()->pluck('title', 'id'))
                    ->required(),
                DatePicker::make('date')->default(now())
                    ->label(__('resources.invoice.date'))
                    ->required(),
                DatePicker::make('due_date')->label(__('resources.invoice.due_date')),
                TextInput::make('dept_amount')
                    ->label(__('resources.invoice.dept_amount'))
                    ->formatStateUsing(fn($state) => $state / 100)
                    ->mutateDehydratedStateUsing(fn($state) => (float)$state * 100)
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_advance')
                    ->label(__('resources.invoice.advance'))
                    ->inline(false),

                TextInput::make('currency')
                    ->label(__('resources.invoice.currency'))
                    ->default('EUR')
                    ->readOnly()
                    ->required(),

                TextInput::make('comment')
                    ->label(__('resources.invoice.comment'))
                    ->default(''),

                Toggle::make('is_paid')
                    ->label(__('resources.invoice.is_paid'))
                    ->inline(false)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $tenant = Filament::getTenant();

        $clients = Filament::getTenant()->clients->mapWithKeys(function (Client $client, $key) {
            return [$client->getKey() => $client->title];
        });

        return $table
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label(__('resources.invoice.invoice_number')),
                Tables\Columns\TextColumn::make('client_id')
                    ->formatStateUsing(fn ($state) => $clients[$state] ?? '')
                    ->label(__('resources.invoice.client')),
                Tables\Columns\IconColumn::make('is_paid')
                    ->alignCenter()
                    ->label(__('resources.invoice.is_paid')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('resources.invoice.amount_total'))
                    ->state(function (Invoice $invoice): string {
                        return Money::defaultFormat($invoice->getTotalWithVatAndTax());
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('resources.invoice.date'))
                    ->formatStateUsing(fn($state) => $state->format('Y.m.d'))
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(__('resources.invoice.client'))
                    ->options($clients),
                Tables\Filters\SelectFilter::make('is_paid')
                    ->label(__('resources.invoice.is_paid'))
                    ->options([
                        false => __('filament-forms::components.radio.boolean.false'),
                        true => __('filament-forms::components.radio.boolean.true'),
                    ]),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('pages.filter_with_deleted'))

            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-m-magnifying-glass')
                    ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                        'tenant' => $tenant,
                        'record' => $record,
                        'method' => 'inline',
                    ]))
                    ->label(__('pages.invoices.view'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                        'tenant' => $tenant,
                        'record' => $record,
                        'method' => 'download',
                    ]))
                    ->label(__('pages.invoices.download'))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
            'pdf' => ViewInvoicePdf::route('/{record}/pdf/{method}'),
        ];
    }

    public static function getLabel(): string
    {
        return __('pages.invoices.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages.invoices.plural');
    }
}
