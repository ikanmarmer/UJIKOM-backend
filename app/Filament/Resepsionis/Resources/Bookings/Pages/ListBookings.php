<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Booking Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),

            'today_checkin' => Tab::make('Check-in Hari Ini')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereDate('check_in_date', today())
                        ->whereIn('status', ['confirmed', 'active'])
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->whereDate('check_in_date', today())
                        ->whereIn('status', ['confirmed', 'active'])
                        ->count()
                ),

            'today_checkout' => Tab::make('Check-out Hari Ini')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereDate('check_out_date', today())
                        ->where('status', 'active')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->whereDate('check_out_date', today())
                        ->where('status', 'active')
                        ->count()
                ),

            'pending' => Tab::make('Menunggu')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'pending')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'pending')
                        ->count()
                )
                ->badgeColor('warning'),

            'confirmed' => Tab::make('Terkonfirmasi')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'confirmed')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'confirmed')
                        ->count()
                )
                ->badgeColor('info'),

            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'active')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'active')
                        ->count()
                )
                ->badgeColor('primary'),

            'completed' => Tab::make('Selesai')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'completed')
                )
                ->badgeColor('success'),
        ];
    }
}
