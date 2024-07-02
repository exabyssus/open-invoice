<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateUser;
use App\Filament\Pages\EditUser;
use App\Filament\Pages\ListUsers;
use App\Models\User;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationGroup = 'Iestatījumi';

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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('pages.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages.users.plural');
    }
}
