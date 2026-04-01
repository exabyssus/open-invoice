<?php

it('redirects to panel on home page', function () {
    auth()->logout();

    $response = $this->get('/');

    $response->assertRedirect('/panel');
    $response->assertStatus(302);
});
