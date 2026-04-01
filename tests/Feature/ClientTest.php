<?php

use App\Filament\Pages\CreateClient;
use App\Filament\Pages\EditClient;
use App\Filament\Pages\ListClients;
use App\Models\Client;
use Livewire\Livewire;

it('can render client list page', function () {
    $this->get(\App\Filament\Resources\ClientResource::getUrl('index', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('can list clients', function () {
    $clients = Client::factory()
        ->count(3)
        ->create(['company_id' => $this->company->id]);

    Livewire::test(ListClients::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords($clients);
});

it('can render create client page', function () {
    $this->get(\App\Filament\Resources\ClientResource::getUrl('create', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('can create a client', function () {
    Livewire::test(CreateClient::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'title' => 'Test Client SIA',
            'registration_number' => '40001234567',
            'vat_number' => 'LV40001234567',
            'address' => 'Brīvības iela 1, Rīga',
            'bank_name' => 'Swedbank',
            'bank_swift' => 'HABALV22',
            'bank_iban' => 'LV80HABA0551040060112',
            'agreement_number' => 'AGR-001',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clients', [
        'title' => 'Test Client SIA',
        'registration_number' => '40001234567',
        'company_id' => $this->company->id,
    ]);
});

it('requires client title', function () {
    Livewire::test(CreateClient::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'title' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);
});

it('can render edit client page', function () {
    $client = Client::factory()->create(['company_id' => $this->company->id]);

    $this->get(\App\Filament\Resources\ClientResource::getUrl('edit', [
        'tenant' => $this->company,
        'record' => $client,
    ]))->assertOk();

});

it('can update a client', function () {
    $client = Client::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Old Name',
    ]);

    Livewire::test(EditClient::class, [
        'tenant' => $this->company,
        'record' => $client->id,
    ])
        ->fillForm([
            'title' => 'Updated Client Name',
            'address' => 'Jaunā iela 5, Rīga',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'title' => 'Updated Client Name',
        'address' => 'Jaunā iela 5, Rīga',
    ]);

});
