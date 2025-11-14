<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Hotel;
use App\Models\Booking;
use App\Models\RoomCategory;
use App\Models\HotelStaff;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $ownerId = auth()->id();

        // Total hotels
        $totalHotels = Hotel::where('owner_id', $ownerId)->count();
        $activeHotels = Hotel::where('owner_id', $ownerId)->where('status', 'active')->count();

        // Total bookings
        $totalBookings = Booking::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))->count();
        $activeBookings = Booking::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))
            ->whereIn('status', ['confirmed', 'active'])
            ->count();

        // Total revenue
        $totalRevenue = Booking::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $monthlyRevenue = Booking::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');

        // Total rooms
        $totalRooms = RoomCategory::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))
            ->sum('total_rooms');

        // Active staff
        $activeStaff = HotelStaff::whereHas('hotel', fn($q) => $q->where('owner_id', $ownerId))
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Total Hotel', $totalHotels)
                ->description("{$activeHotels} hotel aktif")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pemesanan Aktif', $activeBookings)
                ->description("{$totalBookings} total pemesanan")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart([3, 5, 6, 8, 7, 9, 10, 12]),

            Stat::make('Pendapatan Bulanan', 'IDR ' . number_format($monthlyRevenue, 0, ',', '.'))
                ->description('IDR ' . number_format($totalRevenue, 0, ',', '.') . ' total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([12, 15, 18, 20, 22, 25, 27, 30]),

            Stat::make('Total Kamar', $totalRooms)
                ->description("Di semua hotel")
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),

            Stat::make('Staf Aktif', $activeStaff)
                ->description("Resepsionis")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
