<?php

use App\Models\Invoice;
use App\NumberStyles;
use Filament\Facades\Filament;

$generator = new \App\Services\GenerateInvoiceNumber();

beforeEach(function () {
    $this->currentCompany = Filament::getTenant();
});

afterEach(function () {
    $this->currentCompany->invoices()->forceDelete();
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
