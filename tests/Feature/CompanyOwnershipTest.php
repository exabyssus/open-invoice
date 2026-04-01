<?php

use App\Filament\Pages\CompanyProfile;
use App\Filament\Pages\RegisterCompany;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('user who creates company becomes owner', function () {
    Livewire::test(RegisterCompany::class)
        ->fillForm([
            'title' => 'My New Company',
            'registration_number' => '12345678',
            'address' => 'Test Street 1',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $company = Company::where('title', 'My New Company')->first();
    expect($company)->not->toBeNull();
    expect($company->owner_id)->toBe($this->user->id);
    expect($company->users()->where('users.id', $this->user->id)->exists())->toBeTrue();
});

it('any user can access register company page', function () {
    expect(RegisterCompany::canView())->toBeTrue();
});

it('owner can see delete button on company profile', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->assertActionVisible('deleteCompany');
});

it('non-owner cannot see delete button on company profile', function () {
    $otherUser = User::factory()->create();
    $otherUser->companies()->attach($this->company);

    $this->actingAs($otherUser);
    Filament::setTenant($this->company);

    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->assertActionHidden('deleteCompany');
});

it('owner can delete their company', function () {
    $companyId = $this->company->id;

    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->callAction('deleteCompany');

    $this->assertDatabaseMissing('companies', ['id' => $companyId]);
});

it('company persists when owner is deleted', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->ownedBy($owner)->create();
    $owner->companies()->attach($company);

    $otherUser = User::factory()->create();
    $otherUser->companies()->attach($company);

    $companyId = $company->id;
    $owner->forceDelete();

    $company->refresh();
    expect($company->exists)->toBeTrue();
    expect($company->owner_id)->toBeNull();
});

it('company profile shows owner name', function () {
    Livewire::test(CompanyProfile::class, [
        'tenant' => $this->company,
    ])
        ->assertSee($this->user->name);
});

it('super-admin can transfer ownership via company resource', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->companies()->attach($this->company);
    $this->actingAs($superAdmin);
    Filament::setTenant($this->company);

    $newOwner = User::factory()->create();

    Livewire::test(EditCompany::class, [
        'tenant' => $this->company,
        'record' => $this->company->id,
    ])
        ->fillForm([
            'owner_id' => $newOwner->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($this->company->owner_id)->toBe($newOwner->id);
    // New owner should be auto-attached to pivot
    expect($this->company->users()->where('users.id', $newOwner->id)->exists())->toBeTrue();
});

it('owner can see members page', function () {
    $this->get(\App\Filament\Resources\MemberResource::getUrl('index', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('members page lists tenant users', function () {
    $member = User::factory()->create();
    $this->company->users()->attach($member);

    Livewire::test(\App\Filament\Resources\MemberResource\Pages\ListMembers::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords([$this->user, $member]);
});

it('isOwnedBy returns correct result', function () {
    $otherUser = User::factory()->create();

    expect($this->company->isOwnedBy($this->user))->toBeTrue();
    expect($this->company->isOwnedBy($otherUser))->toBeFalse();
});
