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

it('sends an existing user back to the invitation after login', function () {
    $invitation = \App\Models\CompanyInvitation::create([
        'company_id' => $this->company->id,
        'email' => $this->user->email,
        'token' => 'tok123',
    ]);
    auth()->logout();

    $this->get(route('invitation.accept', $invitation->token))->assertRedirect('/panel/login');

    expect(session('url.intended'))->toBe(route('invitation.accept', $invitation->token));
});

it('sends a session that outlived a password change to the login page', function () {
    $this->withSession(['password_hash_web' => 'hash-from-before-the-change'])
        ->get('/panel/' . $this->company->id)
        ->assertRedirect('/panel/login');
});
