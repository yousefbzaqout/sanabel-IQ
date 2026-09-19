<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\SetFilamentAdminLocale;
use App\Http\Middleware\SetFilamentTenantContext;
use App\Filament\Widgets\TenantOverviewStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('سنابل IQ - بوابة الإدارة')
            ->brandLogo(asset('brand/logo-official.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('brand/logo.svg'))
            ->colors([
                'primary' => Color::hex('#D97706'),
                'secondary' => Color::hex('#0D9488'),
                'gray' => Color::Slate,
                'danger' => Color::hex('#EF4444'),
                'success' => Color::hex('#10B981'),
                'warning' => Color::hex('#F59E0B'),
                'info' => Color::hex('#0284C7'),
            ])
            ->font('Tajawal')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                TenantOverviewStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetFilamentAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetFilamentTenantContext::class,
            ]);
    }
}
