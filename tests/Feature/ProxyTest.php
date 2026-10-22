<?php

it('uses https urls behind the https proxy', function () {
    auth()->logout();

    $response = $this->call('GET', '/panel/login', server: ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_HOST' => 'rekini.test', 'HTTP_X_FORWARDED_PORT' => '443', 'REMOTE_ADDR' => '172.18.0.5']);

    $response->assertOk();
    expect($response->getContent())
        ->toMatch('#https://rekini\.test/livewire[^"\']*/update#')
        ->not->toMatch('#http://rekini\.test/livewire#');
});
