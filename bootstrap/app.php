<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The app only receives traffic from the Coolify proxy (Traefik behind Cloudflare), which sets X-Forwarded-Proto.
        // Without this Laravel thinks it is on http and the admin's Livewire calls are blocked as mixed content.
        $middleware->trustProxies(at: '*');
        // AuthenticateSession ends sessions after a password change; send them to the panel login (the app has no `login` route)
        $middleware->redirectGuestsTo(fn () => route('filament.panel.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
