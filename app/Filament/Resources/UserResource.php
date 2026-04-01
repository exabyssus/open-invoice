<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CreateUser;
use App\Filament\Pages\EditUser;
use App\Filament\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static bool $isScopedToTenant = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->required(),
                TextInput::make('password')->password(),
                Toggle::make('is_super_admin')
                    ->label('Super Admin')
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->label('Admin')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('companies_count')
                    ->counts('companies')
                    ->label('Companies'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-finger-print')
                    ->color('gray')
                    ->hidden(fn (User $record) => $record->id === auth()->id())
                    ->url(fn (User $record) => route('impersonate.start', $record)),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return 'User';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Users';
    }
}
