<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FilamentTestCase extends BaseTestCase
{
    public Company $company;
    public User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->ownedBy($this->user)->create();

        $this->user->companies()->attach($this->company);

        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }
}
