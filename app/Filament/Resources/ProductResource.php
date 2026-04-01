<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Units;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('product_number'),
                TextInput::make('price')
                    ->formatStateUsing(fn($state) => $state ? number_format($state / 100, 2, ',', '') : null)
                    ->mutateDehydratedStateUsing(fn($state) => (int)round((float)str_replace(',', '.', $state) * 100))
                    ->required(),
                TextInput::make('default_quantity')
                    ->formatStateUsing(fn($state) => $state ? str_replace('.', ',', $state) : '1')
                    ->mutateDehydratedStateUsing(fn($state) => (float)str_replace(',', '.', $state))
                    ->default('1')
                    ->required(),
                Select::make('unit')
                    ->options(Units::asOptions())
                    ->default('UNIT')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('product_number')->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn($state) => number_format($state / 100, 2, ',', '')),
                Tables\Columns\TextColumn::make('default_quantity')
                    ->formatStateUsing(fn($state) => str_replace('.', ',', $state)),
                Tables\Columns\TextColumn::make('unit')
                    ->formatStateUsing(fn($state) => Units::asOptions()[$state] ?? $state),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return 'Product';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Products';
    }
}
