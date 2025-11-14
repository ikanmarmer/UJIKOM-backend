<?php

namespace App\Filament\Resepsionis\Widgets;

use App\Models\Booking;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $hotelIds = auth()->user()->managedHotels()->pluck('hotels.id');

        $todayCheckins = Booking::whereIn('hotel_id', $hotelIds)
            ->whereDate('check_in_date', today())
            ->whereIn('status', ['confirmed', 'active'])
            ->count();

        $todayCheckouts = Booking::whereIn('hotel_id', $hotelIds)
            ->whereDate('check_out_date', today())
            ->where('status', 'active')
            ->count();

        $activeBookings = Booking::whereIn('hotel_id', $hotelIds)
            ->where('status', 'active')
            ->count();

        $availableRooms = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })
            ->where('status', 'available')
            ->count();

        $occupiedRooms = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })
            ->where('status', 'occupied')
            ->count();

        $cleaningRooms = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })
            ->where('status', 'cleaning')
            ->count();

        return [
            Stat::make('Check-in Hari Ini', $todayCheckins)
                ->description('Tamu yang akan check-in')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 5, 2, 8, 4, $todayCheckins]),

            Stat::make('Check-out Hari Ini', $todayCheckouts)
                ->description('Tamu yang akan check-out')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning')
                ->chart([3, 7, 2, 9, 4, 6, $todayCheckouts]),

            Stat::make('Booking Aktif', $activeBookings)
                ->description('Tamu yang sedang menginap')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Kamar Tersedia', $availableRooms)
                ->description("{$occupiedRooms} terisi, {$cleaningRooms} pembersihan")
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),
        ];
    }
}
