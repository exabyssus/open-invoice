<?php

namespace App\Filament;

use App\Support\Money;
use App\Units;
use App\Vat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('resources.invoice_line.title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label(__('resources.invoice_line.price'))
                    ->formatStateUsing(fn(\Money\Money|null $state) => $state?->getAmount() / 100)
                    ->mutateDehydratedStateUsing(fn($state) => (float)$state * 100)
                    ->numeric('decimal'),
                Forms\Components\TextInput::make('quantity')
                    ->label(__('resources.invoice_line.quantity'))
                    ->numeric('integer'),
                Forms\Components\Select::make('vat')
                    ->label(__('resources.invoice_line.vat'))
                    ->options(Vat::asOptions())->default(Vat::VAT_21),
                Forms\Components\Select::make('unit')
                    ->label(__('resources.invoice_line.unit'))
                    ->options(Units::asOptions()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('resources.invoice_line.title')),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('resources.invoice_line.price'))
                    ->alignCenter()
                    ->formatStateUsing(fn(\Money\Money|null $state) => Money::defaultFormat($state)),
                Tables\Columns\TextColumn::make('vat')
                    ->label(__('resources.invoice_line.vat'))
                    ->alignCenter()
                    ->formatStateUsing(fn(string $state) => Vat::asOptions()[$state]),
                Tables\Columns\TextColumn::make('quantity')
                    ->alignCenter()
                    ->label(__('resources.invoice_line.quantity')),
                Tables\Columns\TextColumn::make('unit')
                    ->label(__('resources.invoice_line.unit'))
                    ->alignCenter()
                    ->formatStateUsing(fn(string $state) => Units::asOptions()[$state]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getModelLabel(): ?string
    {
        return __('pages.invoices.invoice_line');
    }
}
