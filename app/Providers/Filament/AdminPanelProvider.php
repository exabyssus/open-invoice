<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\CompanyProfile;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\InvoiceAmountOverview;
use App\Filament\Widgets\InvoiceStatusOverview;
use App\Models\Company;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('panel')
            ->path('panel')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->login()
            ->profile()
            ->navigationItems([
                NavigationItem::make('Company Profile')
                 ->url(fn() => CompanyProfile::getUrl())
                 ->label(fn() => CompanyProfile::getLabel())
                 ->isActiveWhen(fn (): bool => request()->routeIs('filament.panel.tenant.profile'))
                 ->group('Iestatījumi')
            ])
            ->tenant(Company::class)
            ->tenantProfile(CompanyProfile::class)
            ->tenantMenuItems([
            ])
            ->tenantMenu(fn() => auth()->user()->companies()->count() > 1)
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->resources([
                ClientResource::class,
                InvoiceResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                InvoiceAmountOverview::class,
                InvoiceStatusOverview::class
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
