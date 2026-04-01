<?php

it('redirects guests to login page', function () {
    auth()->logout();

    $this->get('/')->assertRedirect('/panel');
});

it('shows login page', function () {
    auth()->logout();

    $this->get('/panel/login')->assertOk();
});

it('rejects login with invalid credentials', function () {
    auth()->logout();

    $this->post('/panel/login', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

it('allows authenticated user to access dashboard', function () {
    $this->get('/panel/' . $this->company->id)->assertOk();
});

it('prevents access to other tenants', function () {
    $otherCompany = \App\Models\Company::factory()->create();

    $this->get('/panel/' . $otherCompany->id)
        ->assertStatus(404);
});
