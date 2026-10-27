<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\GenerateInvoicePdf;
use App\Support\Money;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class InvoiceMail extends Mailable
{
    /** $subjectLine and $body are the hand-edited mail from the send dialog; null = the standard text. */
    public function __construct(public Invoice $invoice, public ?string $subjectLine = null, public ?string $body = null) {}

    public static function defaultSubject(Invoice $invoice): string
    {
        return __('mail.invoice.subject', ['number' => $invoice->invoice_number, 'company' => $invoice->company->title]);
    }

    public static function defaultBody(Invoice $invoice): string
    {
        return trim(view('emails.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'total' => Money::defaultFormat($invoice->getTotalWithVatAndTax()),
        ])->render());
    }

    public static function attachmentName(Invoice $invoice): string
    {
        return Str::slug($invoice->invoice_number.'-'.$invoice->company->title).'.pdf';
    }

    public function envelope(): Envelope
    {
        $company = $this->invoice->company;
        $owner = $company->owner;

        return new Envelope(
            from: new Address(config('mail.from.address'), $company->title),
            replyTo: $owner ? [new Address($owner->email, $owner->name)] : [],
            subject: $this->subjectLine ?? self::defaultSubject($this->invoice),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.raw', with: ['body' => $this->body ?? self::defaultBody($this->invoice)]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => (new GenerateInvoicePdf)($this->invoice)->getContent(),
                self::attachmentName($this->invoice),
            )->withMime('application/pdf'),
        ];
    }
}
