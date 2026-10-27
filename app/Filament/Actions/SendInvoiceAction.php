<?php

namespace App\Filament\Actions;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** "Send" on an invoice: a modal with the mail (to, subject, text) prefilled and editable, the PDF goes along; "Resend" once sent. */
class SendInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('send')
            ->icon('heroicon-m-paper-airplane')
            ->label(fn (Invoice $record) => $record->sent_at ? __('pages.invoices.resend') : __('pages.invoices.send'))
            ->modalHeading(fn (Invoice $record) => __('pages.invoices.send_heading', ['number' => $record->invoice_number]))
            ->modalDescription(fn (Invoice $record) => __('pages.invoices.send_attachment', ['file' => InvoiceMail::attachmentName($record)]))
            ->modalSubmitActionLabel(__('pages.invoices.send'))
            ->fillForm(fn (Invoice $record) => [
                'to' => $record->client?->email,
                'subject' => InvoiceMail::defaultSubject($record),
                'body' => InvoiceMail::defaultBody($record),
            ])
            ->schema([
                TextInput::make('to')->label(__('pages.invoices.send_to'))->email()->required(),
                TextInput::make('subject')->label(__('pages.invoices.send_subject'))->required(),
                Textarea::make('body')->label(__('pages.invoices.send_body'))->rows(12)->required(),
            ])
            ->action(function (Invoice $record, array $data) {
                try {
                    Mail::to($data['to'])->send(new InvoiceMail($record, $data['subject'], $data['body']));
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title(__('pages.invoices.send_failed'))->send();

                    return;
                }

                $record->forceFill(['sent_at' => now()])->saveQuietly();
                Notification::make()->success()->title(__('pages.invoices.sent'))->send();
            });
    }
}
