<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class MemberResource extends Resource
{
    protected static ?string $model = User::class;
    protected static bool $isScopedToTenant = false;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'Iestatījumi';

    public static function getEloquentQuery(): Builder
    {
        $userIds = Filament::getTenant()->users()->pluck('users.id');
        return parent::getEloquentQuery()->whereIn('id', $userIds);
    }

    public static function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $isOwner = $tenant->isOwnedBy(auth()->user()) || auth()->user()->isSuperAdmin();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->state(function (User $record) use ($tenant) {
                        if ($tenant->owner_id === $record->id) {
                            return 'Owner';
                        }
                        return 'Member';
                    }),
            ])
            ->actions([
                Actions\Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $isOwner && $tenant->owner_id !== $record->id)
                    ->action(function (User $record) use ($tenant) {
                        $tenant->users()->detach($record->id);
                    }),
            ])
            ->headerActions([
                Actions\Action::make('invite')
                    ->label('Invite')
                    ->icon('heroicon-o-envelope')
                    ->visible($isOwner)
                    ->form([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                    ])
                    ->action(function (array $data) use ($tenant) {
                        $email = $data['email'];

                        // Check if already a member
                        if ($tenant->users()->where('email', $email)->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('Already a member')
                                ->body("{$email} is already a member of this company.")
                                ->send();
                            return;
                        }

                        // Check if invitation already pending
                        $existing = $tenant->invitations()
                            ->forEmail($email)
                            ->pending()
                            ->first();

                        if ($existing) {
                            Notification::make()
                                ->warning()
                                ->title('Invitation already sent')
                                ->body("A pending invitation for {$email} already exists.")
                                ->send();
                            return;
                        }

                        $invitation = CompanyInvitation::create([
                            'company_id' => $tenant->id,
                            'email' => $email,
                            'token' => Str::random(64),
                        ]);

                        // Send notification
                        $user = User::where('email', $email)->first();
                        if ($user) {
                            $user->notify(new CompanyInvitationNotification($invitation));
                        } else {
                            NotificationFacade::route('mail', $email)
                                ->notify(new CompanyInvitationNotification($invitation));
                        }

                        Notification::make()
                            ->success()
                            ->title('Invitation sent')
                            ->body("Invitation sent to {$email}")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
        ];
    }

    public static function getLabel(): string
    {
        return 'Member';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Members';
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'members';
    }
}
