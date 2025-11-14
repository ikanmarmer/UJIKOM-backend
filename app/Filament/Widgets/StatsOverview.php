<?php

namespace App\Filament\Widgets;

use App\Models\Hotel;
use App\Models\Booking;
use App\Models\User;
use App\Models\Review;
use App\Enums\ReviewStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Booking::where('payment_status', 'paid')->sum('total_price');
        $monthlyRevenue = Booking::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->sum('total_price');

        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $activeBookings = Booking::where('status', 'active')->count();

        $totalUsers = User::where('role', 'user')->count();
        $newUsersThisMonth = User::where('role', 'user')
            ->whereMonth('created_at', now()->month)
            ->count();

        $totalHotels = Hotel::count();
        $activeHotels = Hotel::where('status', 'active')->count();
        $pendingHotels = Hotel::where('status', 'pending')->count();

        $pendingReviews = Review::where('status', ReviewStatus::PENDING)->count();
        $totalReviews = Review::where('status', ReviewStatus::APPROVED)->count();

        return [
            Stat::make('Total Pendapatan', 'IDR ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Bulanan: IDR ' . number_format($monthlyRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Pemesanan', number_format($totalBookings))
                ->description($pendingBookings . ' menunggu, ' . $confirmedBookings . ' terkonfirmasi')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart([3, 5, 7, 4, 6, 8, 5, 7])
                ->icon('heroicon-o-calendar'),

            Stat::make('Pemesanan Aktif', number_format($activeBookings))
                ->description('Sedang check-in')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->icon('heroicon-o-home-modern'),

            Stat::make('Total Pengguna', number_format($totalUsers))
                ->description($newUsersThisMonth . ' baru bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([2, 3, 4, 3, 5, 4, 6, 5])
                ->icon('heroicon-o-users'),

            Stat::make('Hotels', number_format($totalHotels))
                ->description($activeHotels . ' aktif' . ($pendingHotels > 0 ? ', ' . $pendingHotels . ' menunggu' : ''))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color($pendingHotels > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Ulasan', number_format($totalReviews) . ' disetujui')
                ->description($pendingReviews > 0 ? $pendingReviews . ' menunggu persetujuan' : 'Semua telah ditinjau')
                ->descriptionIcon($pendingReviews > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingReviews > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-star'),
        ];
    }

    protected static ?int $sort = 1;
}
