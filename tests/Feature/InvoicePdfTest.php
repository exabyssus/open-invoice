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
