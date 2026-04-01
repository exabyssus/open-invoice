<?php

use App\Filament\Pages\CompanyProfile;
use App\NumberStyles;
use Livewire\Livewire;

it('can render company profile page', function () {
    $this->get(\App\Filament\Pages\CompanyProfile::getUrl(tenant: $this->company))
        ->assertOk();
});

it('can update company settings', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'title' => 'Updated Company SIA',
            'registration_number' => '40009999999',
            'vat_number' => 'LV40009999999',
            'address' => 'Elizabetes iela 10, Rīga',
            'show_accounts' => true,
            'bank_name' => 'Citadele',
            'bank_swift' => 'PARXLV22',
            'bank_iban' => 'LV35PARX0000000000001',
            'number_style' => \App\NumberStyles::NUMBER_STYLE_NONE->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($this->company->title)->toBe('Updated Company SIA');
    expect($this->company->registration_number)->toBe('40009999999');
    expect($this->company->vat_number)->toBe('LV40009999999');
    expect($this->company->address)->toBe('Elizabetes iela 10, Rīga');
    expect($this->company->bank_name)->toBe('Citadele');
});

it('can update invoice numbering settings', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'number_prefix' => 'INV-',
            'number_style' => NumberStyles::NUMBER_STYLE_YEARLY->name,
            'number_suffix' => '/2026',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($this->company->number_prefix)->toBe('INV-');
    expect($this->company->number_style)->toBe(NumberStyles::NUMBER_STYLE_YEARLY);
    expect($this->company->number_suffix)->toBe('/2026');
});

it('requires company title', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'title' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['title' => 'required']);
});

it('can toggle bank account visibility', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'show_accounts' => false,
            'number_style' => \App\NumberStyles::NUMBER_STYLE_NONE->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($this->company->show_accounts)->toBeFalse();
});
