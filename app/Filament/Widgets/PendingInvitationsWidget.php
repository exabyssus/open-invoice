<?php

namespace App\Filament\Widgets;

use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class PendingInvitationsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $isOwner = $tenant->isOwnedBy(auth()->user()) || auth()->user()->isSuperAdmin();

        return $table
            ->query(
                CompanyInvitation::query()
                    ->where('company_id', $tenant->id)
                    ->pending()
            )
            ->heading(__('pages.pending_invitations.heading'))
            ->paginated(false)
            ->emptyStateHeading(__('pages.pending_invitations.empty'))
            ->columns([
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('pages.pending_invitations.sent'))
                    ->since(),
            ])
            ->actions([
                Actions\Action::make('resend')
                    ->label(__('pages.pending_invitations.resend'))
                    ->icon('heroicon-o-arrow-path')
                    ->visible($isOwner)
                    ->action(function (CompanyInvitation $record) {
                        $user = User::where('email', $record->email)->first();
                        if ($user) {
                            $user->notify(new CompanyInvitationNotification($record));
                        } else {
                            NotificationFacade::route('mail', $record->email)
                                ->notify(new CompanyInvitationNotification($record));
                        }

                        $record->touch();

                        Notification::make()
                            ->success()
                            ->title(__('pages.pending_invitations.resent', ['email' => $record->email]))
                            ->send();
                    }),
                Actions\Action::make('cancel')
                    ->label(__('pages.pending_invitations.cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible($isOwner)
                    ->requiresConfirmation()
                    ->action(fn (CompanyInvitation $record) => $record->delete()),
            ]);
    }
}
