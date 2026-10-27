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

it('fills the invoice number with the customer code of the chosen client', function () {
    $this->company->update(['number_style' => 'NUMBER_STYLE_DATE_SHORT', 'number_prefix' => '', 'number_suffix' => 'CUSTOMER_CODE']);
    $this->client->update(['code' => '056']);
    $other = Client::factory()->create(['company_id' => $this->company->id, 'code' => '011']);
    $today = now()->format('dmy');

    Livewire::test(CreateInvoice::class, ['tenant' => $this->company])
        ->assertFormSet(['invoice_number' => "$today-"])
        ->set('data.client_id', $this->client->id)
        ->assertFormSet(['invoice_number' => "$today-056"])
        ->set('data.client_id', $other->id)
        ->assertFormSet(['invoice_number' => "$today-011"]) // still the generated number: follows the client
        ->set('data.invoice_number', 'TYPED-1')
        ->set('data.client_id', $this->client->id)
        ->assertFormSet(['invoice_number' => 'TYPED-1']);  // typed over: left alone
});

it('can set the customer code on a client', function () {
    Livewire::test(\App\Filament\Pages\EditClient::class, ['tenant' => $this->company, 'record' => $this->client->getRouteKey()])
        ->fillForm(['code' => 'AB-7'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->client->fresh()->code)->toBe('AB-7');
});

it('sends the invoice with an edited mail and then offers Resend', function () {
    $this->client->update(['email' => 'klients@example.test']);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'template' => 'default',
        'sent_at' => null,
    ]);

    $list = Livewire::test(ListInvoices::class, ['tenant' => $this->company])
        ->mountTableAction('send', $invoice)
        ->assertTableActionDataSet(fn (array $data) => $data['to'] === 'klients@example.test'
            && str_contains($data['subject'], $invoice->invoice_number)
            && str_contains($data['body'], $invoice->invoice_number))
        ->setTableActionData(['to' => 'cits@example.test', 'subject' => 'Mans temats', 'body' => 'Mans teksts <b>'])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $mail = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    expect($mail->getTo()[0]->getAddress())->toBe('cits@example.test')
        ->and($mail->getSubject())->toBe('Mans temats')
        ->and($mail->getTextBody())->toContain('Mans teksts <b>')
        ->and($mail->getAttachments()[0]->getBody())->toStartWith('%PDF')
        ->and($invoice->fresh()->sent_at)->not->toBeNull();

    $list->assertTableActionExists('send', fn ($action) => $action->getLabel() === __('pages.invoices.resend'), $invoice);
});
