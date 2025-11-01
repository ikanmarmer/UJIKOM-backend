<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class LoginPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('login')
            ->path('')
            ->login()
            ->brandName('Login')
            ->authGuard('web')
            ->font('Poppins')
            ->colors([
                'primary' => Color::hex('#475569'),    // Slate
                'secondary' => Color::hex('#0ea5e9'),  // Sky blue
                'tertiary' => Color::hex('#8b5cf6'),   // Purple
                'success' => Color::hex('#15803d'),    // Green
                'warning' => Color::hex('#eab308'),    // Yellow
                'danger' => Color::hex('#be123c'),     // Rose red
                'gray' => Color::hex('#6b7280'),       // Gray
                'info' => Color::hex('#0d9488'),       // Teal
            ])
            // ->discoverResources(in: app_path('Filament/Login/Resources'), for: 'App\Filament\Login\Resources')
            // ->discoverPages(in: app_path('Filament/Login/Pages'), for: 'App\Filament\Login\Pages')
            // ->pages([
            //
            // ])
            // ->discoverWidgets(in: app_path('Filament/Login/Widgets'), for: 'App\Filament\Login\Widgets')
            // ->widgets([
            //
            //
            // ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->viteTheme('resources/css/filament/admin/theme.css');
    }
}
