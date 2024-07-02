<?php

namespace Tests\Feature;

use Tests\FilamentTestCase;

class HomePageFilamentTest extends FilamentTestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_login_page(): void
    {
        // Test without active user session
        auth()->logout();

        $response = $this->get('/');

        $response->assertRedirect('/panel/login');
        $response->assertStatus(302);
    }
}
