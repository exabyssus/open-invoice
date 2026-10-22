<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    Storage::disk('private')->put('legacy-pdfs/sia_bookwork/161026-056-ZS-CIŅI-abc12.pdf', '%PDF-old');
    Storage::disk('private')->put('legacy-pdfs/sia_bookwork/notes.txt', 'secret');
    auth()->logout();
});

it('serves an old invoice link without login', function () {
    $response = $this->get('/docs/sia_bookwork/'.rawurlencode('161026-056-ZS-CIŅI-abc12.pdf'));

    expect($response->streamedContent())->toBe('%PDF-old');
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('does not serve anything else', function () {
    $this->get('/docs/sia_bookwork/missing.pdf')->assertNotFound();
    $this->get('/docs/sia_bookwork/notes.txt')->assertNotFound();
    $this->get('/docs/sia_bookwork/..')->assertNotFound();
    $this->get('/docs/..%2F..%2F.env/x.pdf')->assertNotFound();
    $this->get('/docs')->assertNotFound(); // no listing
});

it('serves the old pdf links on the main address', function () {
    $this->get('http://rekini.agw.lv/docs/sia_bookwork/'.rawurlencode('161026-056-ZS-CIŅI-abc12.pdf'))->assertOk();
});

it('sends the first address to the main one, keeping the path', function () {
    $this->get('http://manirekini.agw.lv/panel/login?x=1')->assertRedirect(config('app.url').'/panel/login?x=1');
    $this->get('http://manirekini.agw.lv')->assertRedirect(config('app.url').'/');
    $this->get('http://rekini.agw.lv/panel/login')->assertOk(); // the main address is not redirected
});
