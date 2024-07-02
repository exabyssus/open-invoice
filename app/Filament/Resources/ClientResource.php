<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateClient;
use App\Filament\Pages\EditClient;
use App\Filament\Pages\ListClients;
use App\Models\Client;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                TextInput::make('title')
                    ->label(__('resources.client.title'))
                    ->required(),

                Group::make(function () {
                    return [
                        TextInput::make('registration_number')->label(__('resources.client.registration_number')),
                        TextInput::make('vat_number')->label(__('resources.client.vat_number')),
                    ];
                })->columns(2),

                TextInput::make('address')->label(__('resources.client.address')),

                Group::make(function () {
                    return [
                        TextInput::make('bank_name')->label(__('resources.client.bank_name')),
                        TextInput::make('bank_swift')->label(__('resources.client.bank_swift')),
                        TextInput::make('bank_iban')->label(__('resources.client.bank_iban')),
                    ];
                })->columns(3),

                TextInput::make('agreement_number')->label(__('resources.client.agreement_number'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('resources.client.title')),
                Tables\Columns\TextColumn::make('registration_number')->label(__('resources.client.registration_number')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('pages.clients.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages.clients.plural');
    }
}
