<?php

namespace App\Notifications;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CompanyInvitation $invitation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->invitation->company->title;
        $acceptUrl = url("/panel/invitation/{$this->invitation->token}/accept");

        return (new MailMessage)
            ->subject("{$companyName} invites you to join")
            ->greeting('Hello!')
            ->line("**{$companyName}** wants you to join their team.")
            ->action('Accept Invitation', $acceptUrl)
            ->line('Click the button above to accept the invitation.');
    }
}
