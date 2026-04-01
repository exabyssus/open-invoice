<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\CompanyProfile;
use App\Filament\Pages\RegisterCompany;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MemberResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\InvoiceAmountOverview;
use App\Filament\Widgets\InvoicesByClientChart;
use App\Filament\Widgets\InvoiceStatusOverview;
use App\Filament\Widgets\LatestInvoices;
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
            ->renderHook('panels::head.end', fn () => new \Illuminate\Support\HtmlString('
                <style>
                    .fi-page-dashboard .fi-wi-widget { display: flex; flex-direction: column; }
                    .fi-page-dashboard .fi-wi-widget > * { flex: 1; display: flex; flex-direction: column; }
                    .fi-page-dashboard .fi-wi-widget .fi-section { flex: 1; display: flex; flex-direction: column; }
                    .fi-page-dashboard .fi-wi-widget .fi-section-content-ctn { flex: 1; }
                </style>
            '))
            ->renderHook('panels::body.start', function () {
                if (! session()->has('impersonator_id')) {
                    return '';
                }
                $name = auth()->user()?->name ?? 'user';
                $stopUrl = route('impersonate.stop');
                return new \Illuminate\Support\HtmlString("
                    <div style='background:#f59e0b;color:#000;text-align:center;padding:8px 16px;font-size:14px;font-weight:500;'>
                        You are impersonating <strong>{$name}</strong>.
                        <a href=\"{$stopUrl}\" style='color:#000;text-decoration:underline;margin-left:8px;'>Stop impersonating</a>
                    </div>
                ");
            })
            ->authMiddleware([
                Authenticate::class,
            ])
            ->login()
            ->passwordReset()
            ->profile()
            ->navigationItems([
                NavigationItem::make('Company Profile')
                 ->url(fn() => CompanyProfile::getUrl())
                 ->label(fn() => CompanyProfile::getLabel())
                 ->isActiveWhen(fn (): bool => request()->routeIs('filament.panel.tenant.profile'))
                 ->group('Iestatījumi')
            ])
            ->tenant(Company::class)
            ->tenantRegistration(RegisterCompany::class)
            ->tenantProfile(CompanyProfile::class)
            ->tenantMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()->visible(false),
            ])
            ->tenantMenu(true)
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->resources([
                ClientResource::class,
                CompanyResource::class,
                InvoiceResource::class,
                MemberResource::class,
                ProductResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                InvoiceStatusOverview::class,
                InvoiceAmountOverview::class,
                InvoicesByClientChart::class,
                LatestInvoices::class,
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
