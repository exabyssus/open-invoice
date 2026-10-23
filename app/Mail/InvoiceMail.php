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
    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $company = $this->invoice->company;
        $owner = $company->owner;

        return new Envelope(
            from: new Address(config('mail.from.address'), $company->title),
            replyTo: $owner ? [new Address($owner->email, $owner->name)] : [],
            subject: __('mail.invoice.subject', ['number' => $this->invoice->invoice_number, 'company' => $company->title]),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.invoice', with: [
            'invoice' => $this->invoice,
            'company' => $this->invoice->company,
            'total' => Money::defaultFormat($this->invoice->getTotalWithVatAndTax()),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => (new GenerateInvoicePdf)($this->invoice)->getContent(),
                Str::slug($this->invoice->invoice_number.'-'.$this->invoice->company->title).'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
