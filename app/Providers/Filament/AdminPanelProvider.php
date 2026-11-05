<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->brandName(fn () => \App\Support\Brand::name())
            ->brandLogo(fn () => view('filament.partials.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon-32.png'))
            ->colors([
                'primary' => Color::hex('#b32510'),
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Sky,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->sidebarCollapsibleOnDesktop()
            // Initial collapsed state is enforced client-side by
            // sidebar-nav-init.blade.php. Filament's PHP-side ->collapsed()
            // didn't match our translated nav-group labels reliably, and a
            // string-based navigationGroups() call would have to call __()
            // at panel-boot time (before the locale middleware runs).
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => __('Back to website'))
                    ->url(fn () => url('/'))
                    ->icon('heroicon-o-globe-alt')
                    ->openUrlInNewTab(false),
            ])
            // Language dropdown right after the global search.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.partials.locale-switcher')->render(),
            )
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->viteTheme('resources/css/filament/passion-theme.css')
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => view('filament.partials.login-jp-decoration')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.partials.back-to-home-login')->render(),
            )
            // Site fonts (Dela Gothic One for the auth headings).
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => app(\Illuminate\Foundation\Vite::class)->fonts()->toHtml(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.partials.back-to-home-topbar')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                function (): string {
                    $route = request()->route()?->getName() ?? '';

                    // Auth pages (login / register / password-reset / email-verification)
                    // get the Mt Fuji background + falling sakura BEFORE the main content
                    // so they sit behind the form. BODY_END would render them on top.
                    if (str_contains($route, '.auth.')) {
                        return view('filament.partials.login-background')->render();
                    }

                    // Authenticated admin pages: reset cached nav-group state
                    // BEFORE Filament's sidebar Livewire boots. BODY_END is
                    // too late — Filament has already read localStorage by
                    // then and rendered the sidebar in the old state.
                    return view('filament.partials.sidebar-nav-init')->render();
                },
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.sidebar-exclusive-groups')->render()
                    . view('filament.partials.audio-player')->render()
                    . view('filament.partials.demo-switcher')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\AdminWelcomeWidget::class,
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
                \App\Http\Middleware\SetLocale::class,
                \App\Http\Middleware\SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
