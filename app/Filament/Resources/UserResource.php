<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateUser;
use App\Filament\Pages\EditUser;
use App\Filament\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextInput::make('name')->required(),
                TextInput::make('email')->required(),
                TextInput::make('password')->password(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userIds = Filament::getTenant()->users()->pluck('users.id');
        return parent::getEloquentQuery()->whereIn('id', $userIds);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email')
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
