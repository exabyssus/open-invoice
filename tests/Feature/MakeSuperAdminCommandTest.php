<?php

use App\Models\User;

it('can create a new super-admin', function () {
    $this->artisan('make:super-admin', ['--create' => true])
        ->expectsQuestion('Name', 'Admin User')
        ->expectsQuestion('Email', 'admin@test.com')
        ->expectsQuestion('Password', 'password123')
        ->expectsOutput('Super-admin admin@test.com created successfully.')
        ->assertExitCode(0);

    $user = User::where('email', 'admin@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->isSuperAdmin())->toBeTrue();
});

it('can promote existing user to super-admin', function () {
    $user = User::factory()->create(['email' => 'regular@test.com']);

    expect($user->isSuperAdmin())->toBeFalse();

    $this->artisan('make:super-admin', ['--email' => 'regular@test.com'])
        ->expectsOutput('regular@test.com has been promoted to super-admin.')
        ->assertExitCode(0);

    $user->refresh();
    expect($user->isSuperAdmin())->toBeTrue();
});

it('fails when promoting non-existent user', function () {
    $this->artisan('make:super-admin', ['--email' => 'nobody@test.com'])
        ->expectsOutput('User with email nobody@test.com not found.')
        ->assertExitCode(1);
});

it('fails when creating user with existing email', function () {
    User::factory()->create(['email' => 'existing@test.com']);

    $this->artisan('make:super-admin', ['--create' => true])
        ->expectsQuestion('Name', 'Admin')
        ->expectsQuestion('Email', 'existing@test.com')
        ->expectsQuestion('Password', 'password123')
        ->expectsOutput('User with email existing@test.com already exists. Use --email to promote.')
        ->assertExitCode(1);
});

it('warns when user is already super-admin', function () {
    User::factory()->superAdmin()->create(['email' => 'super@test.com']);

    $this->artisan('make:super-admin', ['--email' => 'super@test.com'])
        ->expectsOutput('super@test.com is already a super-admin.')
        ->assertExitCode(0);
});
