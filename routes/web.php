<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\AcceptInvitationRegisterController;
use App\Http\Controllers\ImpersonateController;
use Illuminate\Support\Facades\Route;

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
