<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Services\GenerateInvoicePdf;

beforeEach(function () {
    $this->client = Client::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'template' => 'default',
        'invoice_number' => 'PDF-001',
    ]);

    $this->invoice->lines()->create([
        'title' => 'Service',
        'quantity' => 2,
        'price' => 5000,
        'vat' => 21,
        'unit' => 'HOUR',
    ]);
});

it('generates a PDF response', function () {
    $generator = app(GenerateInvoicePdf::class);
    $response = $generator($this->invoice);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('can access PDF via route inline', function () {
    $url = route('filament.panel.resources.invoices.pdf', [
        'tenant' => $this->company,
        'record' => $this->invoice,
        'method' => 'inline',
    ]);

    $this->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('can download PDF via route', function () {
    $url = route('filament.panel.resources.invoices.pdf', [
        'tenant' => $this->company,
        'record' => $this->invoice,
        'method' => 'download',
    ]);

    $response = $this->get($url);
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('download');
});

it('includes invoice data in PDF', function () {
    $generator = app(GenerateInvoicePdf::class);
    $response = $generator($this->invoice);
    $content = $response->getContent();

    expect(strlen($content))->toBeGreaterThan(0);
});

it('generates a PDF for an invoice of a deleted client', function () {
    $this->client->delete();

    $response = $this->get(route('filament.panel.resources.invoices.pdf', [
        'tenant' => $this->company,
        'record' => $this->invoice,
        'method' => 'inline',
    ]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('labels advance invoices and shows the client agreement number', function () {
    $this->invoice->update(['is_advance' => true]);
    $this->client->update(['agreement_number' => 'AGR-77']);

    $html = view('invoices.default', [
        'invoice' => $this->invoice->fresh(),
        'logo' => null,
        'client' => $this->client,
        'company' => $this->company,
        'lines' => $this->invoice->lines,
        'totalAsText' => '',
    ])->render();

    expect($html)->toContain(mb_strtoupper(__('invoice.advance_invoice')))->toContain('AGR-77');
});

it('generates a PDF for an old invoice without creator and with a line without VAT', function () {
    $this->invoice->forceFill(['created_by' => 0])->save();
    $this->invoice->lines()->create(['title' => 'No VAT', 'quantity' => 1, 'price' => 100, 'vat' => null, 'unit' => 'UNIT']);

    set_error_handler(fn ($no, $str) => throw new ErrorException($str));
    try {
        $response = app(GenerateInvoicePdf::class)($this->invoice->fresh());
    } finally {
        restore_error_handler();
    }

    expect($response->getContent())->toStartWith('%PDF');
});
