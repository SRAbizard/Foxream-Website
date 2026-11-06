<?php

namespace App\Providers\Filament;

use App\Filament\Student\Pages\StudentDashboard;
use App\Filament\Student\Widgets\WelcomeWidget;
use Filament\Enums\ThemeMode;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('dashboard')
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
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop()
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label(fn (): string => __('Back to website'))
                    ->url(fn () => url('/'))
                    ->icon('heroicon-o-globe-alt'),
            ])
            // Language dropdown right after the global search.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.partials.locale-switcher')->render(),
            )
            ->login()
            ->registration(\App\Filament\Auth\Register::class)
            ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/theme.css')
            // Language and light/dark on the auth pages, in the form card.
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn (): string => view('filament.partials.auth-tools')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => view('filament.partials.login-jp-decoration')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.partials.back-to-home-login')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
                fn (): string => view('filament.partials.login-jp-decoration')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
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
                    if (str_contains($route, '.auth.')) {
                        return view('filament.partials.login-background')->render();
                    }
                    // Same nav-version reset as the admin panel — keeps
                    // student-panel sidebar in sync with config changes.
                    return view('filament.partials.sidebar-nav-init')->render();
                },
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.sidebar-exclusive-groups')->render()
                    . view('filament.partials.audio-player')->render()
                    . view('filament.partials.demo-switcher')->render(),
            )
            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\\Filament\\Student\\Resources')
            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\\Filament\\Student\\Pages')
            ->pages([
                StudentDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Student/Widgets'), for: 'App\\Filament\\Student\\Widgets')
            ->widgets([
                WelcomeWidget::class,
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
