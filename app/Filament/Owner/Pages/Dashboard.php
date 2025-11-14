<?php

namespace App\Filament\Owner\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected ?string $heading = '';

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Owner\Widgets\StatsOverview::class,
            \App\Filament\Owner\Widgets\RevenueChart::class,
            \App\Filament\Owner\Widgets\LatestBookings::class,
            \App\Filament\Owner\Widgets\HotelPerformance::class,
        ];
    }
}

