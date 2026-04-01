<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\NumberStyles;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static bool $isScopedToTenant = false;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Owner')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->columnSpanFull()
                    ->required(),
                TextInput::make('registration_number')->required(),
                TextInput::make('vat_number'),
                TextInput::make('address')
                    ->columnSpanFull(),
                Toggle::make('show_accounts')
                    ->inline(false)
                    ->columnSpanFull(),
                Group::make([
                    TextInput::make('bank_name'),
                    TextInput::make('bank_swift'),
                    TextInput::make('bank_iban'),
                ])
                    ->columnSpanFull()
                    ->columns(3),
                Group::make([
                    TextInput::make('number_prefix'),
                    Select::make('number_style')
                        ->options([
                            NumberStyles::NUMBER_STYLE_YEARLY->name => 'Yearly',
                            NumberStyles::NUMBER_STYLE_ALL_TIME->name => 'All time',
                            NumberStyles::NUMBER_STYLE_DATE->name => 'Date',
                            NumberStyles::NUMBER_STYLE_NONE->name => 'None',
                        ]),
                    TextInput::make('number_suffix'),
                ])
                    ->columnSpanFull()
                    ->columns(3),
                Select::make('users')
                    ->relationship('users', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),
                Tables\Columns\TextColumn::make('registration_number'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users'),
                Tables\Columns\TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label('Invoices'),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return 'Company';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Companies';
    }
}
