<?php

use App\Filament\Pages\CreateInvoice;
use App\Filament\Pages\EditInvoice;
use App\Filament\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Vat;
use Livewire\Livewire;

beforeEach(function () {
    $this->client = Client::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

it('can render invoice list page', function () {
    $this->get(\App\Filament\Resources\InvoiceResource::getUrl('index', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('can list invoices', function () {
    $invoices = Invoice::factory()
        ->count(3)
        ->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'template' => 'default',
        ]);

    Livewire::test(ListInvoices::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords($invoices);
});

it('can render create invoice page', function () {
    $this->get(\App\Filament\Resources\InvoiceResource::getUrl('create', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('can create an invoice', function () {
    Livewire::test(CreateInvoice::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'invoice_number' => 'INV-001',
            'client_id' => $this->client->id,
            'date' => '2026-04-01',
            'currency' => 'EUR',
            'dept_amount' => '0,00',
            'comment' => 'Test invoice',
            'lines' => [
                [
                    'title' => 'Web development',
                    'price' => '100,00',
                    'unit' => 'HOUR',
                    'quantity' => '10',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => 'INV-001',
        'client_id' => $this->client->id,
        'company_id' => $this->company->id,
    ]);

    $invoice = Invoice::where('invoice_number', 'INV-001')->first();
    expect($invoice->lines)->toHaveCount(1);
    expect($invoice->lines->first()->title)->toBe('Web development');
    expect($invoice->lines->first()->getRawOriginal('price'))->toBe(10000); // 100.00 EUR in cents
});

it('can create invoice with comma decimals', function () {
    Livewire::test(CreateInvoice::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'invoice_number' => 'INV-COMMA',
            'client_id' => $this->client->id,
            'date' => '2026-04-01',
            'currency' => 'EUR',
            'dept_amount' => '10,50',
            'lines' => [
                [
                    'title' => 'Service',
                    'price' => '25,50',
                    'unit' => 'HOUR',
                    'quantity' => '2,5',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::where('invoice_number', 'INV-COMMA')->first();
    expect($invoice->dept_amount)->toBe(1050); // 10.50 EUR in cents
    expect($invoice->lines->first()->getRawOriginal('price'))->toBe(2550); // 25.50 EUR in cents
    expect((float) $invoice->lines->first()->quantity)->toBe(2.5);
});

it('requires invoice number and client', function () {
    Livewire::test(CreateInvoice::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'invoice_number' => '',
            'client_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'invoice_number' => 'required',
            'client_id' => 'required',
        ]);
});

it('can render edit invoice page', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'template' => 'default',
    ]);

    $this->get(\App\Filament\Resources\InvoiceResource::getUrl('edit', [
        'tenant' => $this->company,
        'record' => $invoice,
    ]))->assertOk();
});

it('can update an invoice', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'template' => 'default',
        'is_paid' => false,
    ]);

    Livewire::test(EditInvoice::class, [
        'tenant' => $this->company,
        'record' => $invoice->id,
    ])
        ->fillForm([
            'invoice_number' => 'INV-UPDATED',
            'is_paid' => true,
            'comment' => 'Updated comment',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $invoice->refresh();
    expect($invoice->invoice_number)->toBe('INV-UPDATED');
    expect($invoice->is_paid)->toBeTrue();
    expect($invoice->comment)->toBe('Updated comment');
});

it('can create invoice with VAT lines', function () {
    $this->company->update(['vat_number' => 'LV40001234567']);

    Livewire::test(CreateInvoice::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'invoice_number' => 'VAT-001',
            'client_id' => $this->client->id,
            'date' => '2026-04-01',
            'currency' => 'EUR',
            'dept_amount' => '0,00',
            'lines' => [
                [
                    'title' => 'Consulting',
                    'price' => '200,00',
                    'unit' => 'HOUR',
                    'quantity' => '5',
                    'vat' => Vat::VAT_21->value,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::where('invoice_number', 'VAT-001')->first();
    expect($invoice->lines->first()->vat)->toBe(21);
});
