<?php

use App\Models\Invoice;
use App\NumberStyles;
use Filament\Facades\Filament;

$generator = new \App\Services\GenerateInvoiceNumber();

beforeEach(function () {
    $this->currentCompany = Filament::getTenant();
});


it('adds suffix and prefix for all time style', function () use ($generator) {

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name,
        'number_prefix' => 'XX-',
        'number_suffix' => ''
    ]);

    expect($generator($this->currentCompany))->toBe('XX-1');

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name,
        'number_prefix' => 'XX-',
        'number_suffix' => '-YY'
    ]);

    expect($generator($this->currentCompany))->toBe('XX-1-YY');

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name,
        'number_prefix' => '',
        'number_suffix' => '-YY'
    ]);

    expect($generator($this->currentCompany))->toBe('1-YY');
});

it('adds does not add suffix for "none" style', function () use ($generator) {

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_NONE->name,
        'number_prefix' => 'XX-',
        'number_suffix' => '-YY'
    ]);

    expect($generator($this->currentCompany))->toBe('');
});

it('counts only this years invoices on "yearly" style', function () use ($generator) {

    Invoice::factory()->count(5)->create([
        'company_id' => $this->currentCompany->id,
        'created_at' => \Carbon\Carbon::now()
    ]);

    Invoice::factory()->count(3)->create([
        'company_id' => $this->currentCompany->id,
        'created_at' => \Carbon\Carbon::now()->startOfYear()->subDay()
    ]);

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_YEARLY->name,
        'number_prefix' => 'XX-',
        'number_suffix' => '-YY'
    ]);

    expect($generator($this->currentCompany))->toBe('XX-6-YY');
});

it('counts all invoices on "all time" style', function () use ($generator) {

    Invoice::factory()->count(5)->create([
        'company_id' => $this->currentCompany->id,
        'created_at' => \Carbon\Carbon::now()
    ]);

    Invoice::factory()->count(3)->create([
        'company_id' => $this->currentCompany->id,
        'created_at' => \Carbon\Carbon::now()->startOfYear()->subDay()
    ]);

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name,
        'number_prefix' => 'XX-',
        'number_suffix' => '-YY'
    ]);

    expect($generator($this->currentCompany))->toBe('XX-9-YY');
});

it('uses current date in "date" style', function () use ($generator) {

    Invoice::factory()->count(1)->create([
        'company_id' => $this->currentCompany->id,
        'created_at' => \Carbon\Carbon::now()->subDays(30)
    ]);

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_DATE->name,
        'number_prefix' => 'DATE-',
        'number_suffix' => ''
    ]);

    \Carbon\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2024, 7, 14));

    expect($generator($this->currentCompany))->toBe('DATE-2024.07.14');
});

it('does not reuse the number of a deleted invoice on "all time" style', function () use ($generator) {

    $invoices = Invoice::factory()->count(3)->create(['company_id' => $this->currentCompany->id]);
    $invoices->last()->delete();

    $this->currentCompany->fill(['number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name]);

    expect($generator($this->currentCompany))->toBe('4');
});

it('starts with DDMMYY- in "date short" style and leaves the rest to be typed', function () use ($generator) {

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_DATE_SHORT->name,
        'number_prefix' => '',
        'number_suffix' => ''
    ]);

    \Carbon\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 10, 16));

    expect($generator($this->currentCompany))->toBe('161026-');
});

it('puts the customer code where CUSTOMER_CODE is', function () use ($generator) {

    $this->currentCompany->fill([
        'number_style' => NumberStyles::NUMBER_STYLE_DATE_SHORT->name,
        'number_prefix' => '',
        'number_suffix' => 'CUSTOMER_CODE'
    ]);
    \Carbon\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 10, 16));
    $client = \App\Models\Client::factory()->create(['company_id' => $this->currentCompany->id, 'code' => '056']);

    expect($generator($this->currentCompany, $client))->toBe('161026-056')
        ->and($generator($this->currentCompany))->toBe('161026-') // no client chosen yet
        ->and($generator($this->currentCompany, \App\Models\Client::factory()->create(['company_id' => $this->currentCompany->id])))->toBe('161026-')
        ->and(\App\Services\GenerateInvoiceNumber::usesCustomerCode($this->currentCompany))->toBeTrue();

    $this->currentCompany->fill(['number_suffix' => '{{CUSTOMER_CODE}}']); // braces are accepted too
    expect($generator($this->currentCompany, $client))->toBe('161026-056');

    $this->currentCompany->fill(['number_prefix' => 'X-', 'number_suffix' => '', 'number_style' => NumberStyles::NUMBER_STYLE_ALL_TIME->name]);
    expect(\App\Services\GenerateInvoiceNumber::usesCustomerCode($this->currentCompany))->toBeFalse();
});
