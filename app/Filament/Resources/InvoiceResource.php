<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateInvoice;
use App\Filament\Pages\EditInvoice;
use App\Filament\Pages\ListInvoices;
use App\Filament\Pages\ViewInvoicePdf;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use BackedEnum;
use App\Services\GenerateInvoiceNumber;
use App\Support\Money;
use App\Units;
use App\Vat;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $label = '';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $numberGenerator = new GenerateInvoiceNumber();
        return $schema
            ->schema([
                TextInput::make('invoice_number')
                    ->label(__('resources.invoice.invoice_number'))
                    ->default($numberGenerator(Filament::getTenant()))
                    ->required(),
                Select::make('client_id')
                    ->label(__('resources.invoice.client'))
                    ->searchable(['title', 'registration_number'])
                    ->options(Client::query()->get()->pluck('title', 'id'))
                    ->createOptionForm(self::getNewClientForm())
                    ->createOptionUsing(function (array $data): int {
                            return Filament::getTenant()->clients()->create($data)->getKey();
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, $old, Get $get, Set $set, string $operation) use ($numberGenerator) {
                        $company = Filament::getTenant();
                        if ($operation !== 'create' || ! GenerateInvoiceNumber::usesCustomerCode($company)) {
                            return;
                        }
                        // only replace a number nobody has typed over
                        $current = $get('invoice_number');
                        if ($current === null || $current === '' || $current === $numberGenerator($company, Client::find($old))) {
                            $set('invoice_number', $numberGenerator($company, Client::find($state)));
                        }
                    })
                    ->required(),
                DatePicker::make('date')->default(now())
                    ->label(__('resources.invoice.date'))
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('resources.invoice.due_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('dept_amount')
                    ->label(__('resources.invoice.dept_amount'))
                    ->formatStateUsing(function ($state) {
                        if (is_string($state) && str_contains($state, ',')) return $state;
                        return number_format(((int) $state) / 100, 2, ',', '');
                    })
                    ->mutateDehydratedStateUsing(fn($state) => (int)round((float)str_replace(',', '.', $state) * 100))
                    ->default('0,00')
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

                Repeater::make('lines')
                    ->relationship()
                    ->label('Rēķina rindas')
                    ->schema([
                        Group::make(function () {
                            return [
                                TextInput::make('title')
                                    ->label(__('resources.invoice_line.title'))
                                    ->columnSpan(2)
                                    ->required()
                                    ->maxLength(255)
                                    ->suffixAction(
                                        \Filament\Actions\Action::make('selectProduct')
                                            ->icon('heroicon-o-cube')
                                            ->tooltip(__('pages.product.choose'))
                                            ->form([
                                                Select::make('product_id')
                                                    ->label(__('pages.products.singular'))
                                                    ->options(fn () => Product::query()->get()->mapWithKeys(fn (Product $p) => [$p->id => $p->getDisplayName()]))
                                                    ->searchable()
                                                    ->required(),
                                            ])
                                            ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set) {
                                                $product = Product::find($data['product_id']);
                                                if (! $product) return;

                                                $title = $product->product_number
                                                    ? "{$product->name} ({$product->product_number})"
                                                    : $product->name;

                                                $set('title', $title);
                                                $set('price', number_format($product->price / 100, 2, ',', ''));
                                                $set('quantity', str_replace('.', ',', (string) $product->default_quantity));
                                                $set('unit', $product->unit);
                                            })
                                    ),
                                TextInput::make('price')
                                    ->label(__('resources.invoice_line.price'))
                                    ->formatStateUsing(fn($state) => $state ? number_format($state / 100, 2, ',', '') : null)
                                    ->mutateDehydratedStateUsing(fn($state) => (int)round((float)str_replace(',', '.', $state) * 100))
                                    ->required(),
                                Select::make('unit')
                                    ->label(__('resources.invoice_line.unit'))
                                    ->required()
                                    ->options(Units::asOptions()),
                                TextInput::make('quantity')
                                    ->label(__('resources.invoice_line.quantity'))
                                    ->formatStateUsing(fn($state) => $state ? str_replace('.', ',', $state) : null)
                                    ->mutateDehydratedStateUsing(fn($state) => (float)str_replace(',', '.', $state))
                                    ->required(),
                                Select::make('vat')
                                    ->label(__('resources.invoice_line.vat'))
                                    ->visible(fn() => Filament::getTenant()->vat_number !== null)
                                    ->required()
                                    ->options(Vat::asOptions())->default(Vat::VAT_21),
                            ];
                        })->columns(Filament::getTenant()->vat_number ? 6 : 5)
                            ->columnSpanFull()
                    ])->columnSpanFull()
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
            ->filtersLayout(Tables\Enums\FiltersLayout::Dropdown)
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label(__('resources.invoice.invoice_number')),
                Tables\Columns\TextColumn::make('client_id')
                    ->formatStateUsing(fn ($state) => $clients[$state] ?? '')
                    ->label(__('resources.invoice.client')),
                Tables\Columns\IconColumn::make('is_paid')
                    ->alignCenter()
                    ->label(__('resources.invoice.is_paid'))
                    ->action(
                        Actions\Action::make('markPaid')
                            ->requiresConfirmation()
                            ->modalHeading(__('resources.invoice.mark_paid'))
                            ->hidden(fn (Invoice $record) => $record->is_paid)
                            ->action(fn (Invoice $record) => $record->update(['is_paid' => true]))
                    ),
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
                Actions\Action::make('view')
                    ->icon('heroicon-m-magnifying-glass')
                    ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                        'tenant' => $tenant,
                        'record' => $record,
                        'method' => 'inline',
                    ]))
                    ->label(__('pages.invoices.view'))
                    ->openUrlInNewTab(),
                Actions\Action::make('download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('filament.panel.resources.invoices.pdf', [
                        'tenant' => $tenant,
                        'record' => $record,
                        'method' => 'download',
                    ]))
                    ->label(__('pages.invoices.download'))
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getNewClientForm(): array
    {
        return [
            TextInput::make('title')
                ->label(__('resources.client.title'))
                ->required(),

            Group::make(function () {
                return [
                    TextInput::make('registration_number')->label(__('resources.client.registration_number')),
                    TextInput::make('vat_number')->label(__('resources.client.vat_number')),
                ];
            })->columns(2)->columnSpanFull(),

            TextInput::make('address')->label(__('resources.client.address')),

            Group::make(function () {
                return [
                    TextInput::make('bank_name')->label(__('resources.client.bank_name')),
                    TextInput::make('bank_swift')->label(__('resources.client.bank_swift')),
                    TextInput::make('bank_iban')->label(__('resources.client.bank_iban')),
                ];
            })->columns(3)->columnSpanFull(),

            TextInput::make('agreement_number')->label(__('resources.client.agreement_number')),

            TextInput::make('code')->label(__('resources.client.code'))->maxLength(32),
        ];
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
