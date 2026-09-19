<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Parent\Pages\ParentDashboard;
use App\Http\Middleware\EnsureActiveChildContext;
use App\Http\Middleware\SetFilamentParentLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ParentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('parent')
            ->path('parent')
            ->brandName('سنابل IQ')
            ->brandLogo(asset('brand/logo-official.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('brand/logo.svg'))
            ->colors([
                'primary' => Color::hex('#F59E0B'),
                'secondary' => Color::hex('#006A61'),
                'gray' => Color::Slate,
                'danger' => Color::hex('#BA1A1A'),
                'success' => Color::hex('#006A61'),
                'warning' => Color::hex('#855300'),
                'info' => Color::hex('#494BD6'),
            ])
            ->font('Tajawal')
            ->viteTheme('resources/css/filament/parent-theme.css')
            ->darkMode(false)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render(
                    <<<'BLADE'
                    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
                    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet" />
                    BLADE
                ),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.parent.hooks.active-child-switcher')->render(),
            )
            ->discoverResources(in: app_path('Filament/Parent/Resources'), for: 'App\\Filament\\Parent\\Resources')
            ->discoverPages(in: app_path('Filament/Parent/Pages'), for: 'App\\Filament\\Parent\\Pages')
            ->pages([
                ParentDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Parent/Widgets'), for: 'App\\Filament\\Parent\\Widgets')
            ->widgets([])
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
                SetFilamentParentLocale::class,
                EnsureActiveChildContext::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}
