<?php

use App\Filament\Pages\ListUsers;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('super-admin can access all tenants', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    $otherCompany = Company::factory()->create();

    expect($superAdmin->canAccessTenant($this->company))->toBeTrue();
    expect($superAdmin->canAccessTenant($otherCompany))->toBeTrue();
});

it('super-admin sees all companies as tenants', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    Company::factory()->count(3)->create();

    $tenants = $superAdmin->getTenants(Filament::getCurrentOrDefaultPanel());

    expect($tenants->count())->toBe(Company::count());
});

it('regular user cannot access other tenants', function () {
    $otherCompany = Company::factory()->create();

    expect($this->user->canAccessTenant($this->company))->toBeTrue();
    expect($this->user->canAccessTenant($otherCompany))->toBeFalse();
});

it('regular user only sees own tenants', function () {
    Company::factory()->count(3)->create();

    $tenants = $this->user->getTenants(Filament::getCurrentOrDefaultPanel());

    expect($tenants->count())->toBe(1);
    expect($tenants->first()->id)->toBe($this->company->id);
});

it('super-admin can access company resource', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    $this->get(CompanyResource::getUrl('index', ['tenant' => $this->company]))
        ->assertOk();
});

it('regular user cannot access company resource', function () {
    $this->get(CompanyResource::getUrl('index', ['tenant' => $this->company]))
        ->assertForbidden();
});

it('super-admin can create a company with owner', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    $owner = User::factory()->create();

    Livewire::test(CreateCompany::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'owner_id' => $owner->id,
            'title' => 'New Company SIA',
            'registration_number' => '40001111111',
            'address' => 'Rīga, Latvija',
            'number_style' => \App\NumberStyles::NUMBER_STYLE_NONE->name,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $company = Company::where('title', 'New Company SIA')->first();
    expect($company->owner_id)->toBe($owner->id);
    // Owner auto-attached to pivot
    expect($company->users()->where('users.id', $owner->id)->exists())->toBeTrue();
});

it('super-admin can edit a company and attach users', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    $newUser = User::factory()->create();

    Livewire::test(EditCompany::class, [
        'tenant' => $this->company,
        'record' => $this->company->id,
    ])
        ->fillForm([
            'title' => 'Updated Company Name',
            'users' => [$this->user->id, $newUser->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($this->company->title)->toBe('Updated Company Name');
    expect($this->company->users()->pluck('users.id')->toArray())
        ->toContain($newUser->id);
});

it('super-admin can list companies', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    $companies = Company::factory()->count(3)->create();

    Livewire::test(ListCompanies::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords($companies);
});

it('super-admin sees all users globally', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    // Users not attached to any company
    $globalUsers = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords($globalUsers);
});

it('regular user cannot access user resource', function () {
    $this->get(\App\Filament\Resources\UserResource::getUrl('index', ['tenant' => $this->company]))
        ->assertForbidden();
});
