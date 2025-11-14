<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ResepsionisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('resepsionis')
            ->path('resepsionis')
            ->login()
            ->font('Poppins')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->spa()
            ->navigationGroups([
                'Manajemen Booking',
                'Manajemen Hotel',
                'Laporan',
            ])
            ->brandName('Resepsionis Panel')
            ->discoverResources(in: app_path('Filament/Resepsionis/Resources'), for: 'App\Filament\Resepsionis\Resources')
            ->discoverPages(in: app_path('Filament/Resepsionis/Pages'), for: 'App\Filament\Resepsionis\Pages')
            ->pages([
                //
            ])
            ->discoverWidgets(in: app_path('Filament/Resepsionis/Widgets'), for: 'App\Filament\Resepsionis\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->viteTheme('resources/css/filament/admin/theme.css');
    }
}
