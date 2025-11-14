<?php

namespace App\Filament\Resepsionis\Resources\Rooms\Pages;

use App\Filament\Resepsionis\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Kamar'),

            'available' => Tab::make('Tersedia')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'available')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'available')
                        ->count()
                )
                ->badgeColor('success'),

            'occupied' => Tab::make('Terisi')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'occupied')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'occupied')
                        ->count()
                )
                ->badgeColor('danger'),

            'cleaning' => Tab::make('Pembersihan')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'cleaning')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'cleaning')
                        ->count()
                )
                ->badgeColor('info'),

            'maintenance' => Tab::make('Perawatan')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'maintenance')
                )
                ->badge(
                    fn() =>
                    static::getResource()::getEloquentQuery()
                        ->where('status', 'maintenance')
                        ->count()
                )
                ->badgeColor('warning'),
        ];
    }
}
