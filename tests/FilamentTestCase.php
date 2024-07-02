<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FilamentTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();

        $this->user->companies()->attach($this->company);

        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }

    protected function tearDown(): void
    {
        $this->company->forceDelete();
        $this->user->forceDelete();
    }
}
