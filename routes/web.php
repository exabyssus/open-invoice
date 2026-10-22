<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\AcceptInvitationRegisterController;
use App\Http\Controllers\ImpersonateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Links of the old rekini.agw.lv keep working: /docs/<company folder>/<file>.pdf is one of the PDFs it issued
Route::get('/docs/{folder}/{file}', function (string $folder, string $file) {
    $path = "legacy-pdfs/$folder/$file";

    abort_unless(
        str_ends_with(strtolower($file), '.pdf')
            && ! in_array($folder, ['.', '..'], true)
            && ! in_array($file, ['.', '..'], true)
            && ! str_contains($folder.$file, "\0")
            && ! str_contains($folder.$file, '\\')
            && Storage::disk('private')->exists($path),
        404,
    );

    return Storage::disk('private')->response($path, null, ['Content-Type' => 'application/pdf'], 'inline');
});

// the old host only serves those links, anything else goes to the new address
Route::domain('rekini.agw.lv')->get('/{any?}', fn () => redirect(config('app.url')))->where('any', '(?!docs/).*');

Route::get('/', function () {
    return redirect(\Filament\Facades\Filament::getUrl());
});

Route::get('/panel/invitation/{token}/accept', AcceptInvitationController::class)
    ->name('invitation.accept');

Route::get('/panel/invitation/{token}/register', [AcceptInvitationRegisterController::class, 'show'])
    ->name('invitation.register');

Route::post('/panel/invitation/{token}/register', [AcceptInvitationRegisterController::class, 'store']);

Route::middleware('auth')->group(function () {
    Route::get('/impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
    Route::get('/impersonate-stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');
});
