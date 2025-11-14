<?php

namespace App\Filament\Resepsionis\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.resepsionis.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Resepsionis\Widgets\StatsOverview::class,
            \App\Filament\Resepsionis\Widgets\TodayBookingsTable::class,
            \App\Filament\Resepsionis\Widgets\RoomStatusChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
