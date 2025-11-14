<?php

namespace App\Filament\Resepsionis\Widgets;

use App\Models\Room;
use Filament\Widgets\ChartWidget;

class RoomStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status Kamar';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $hotelIds = auth()->user()->managedHotels()->pluck('hotels.id');

        $available = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })->where('status', 'available')->count();

        $occupied = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })->where('status', 'occupied')->count();

        $cleaning = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })->where('status', 'cleaning')->count();

        $maintenance = Room::whereHas('roomCategory.hotel', function ($query) use ($hotelIds) {
            $query->whereIn('id', $hotelIds);
        })->where('status', 'maintenance')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Status Kamar',
                    'data' => [$available, $occupied, $cleaning, $maintenance],
                    'backgroundColor' => [
                        'rgb(16, 185, 129)',  // success - available
                        'rgb(239, 68, 68)',   // danger - occupied
                        'rgb(59, 130, 246)',  // info - cleaning
                        'rgb(245, 158, 11)',  // warning - maintenance
                    ],
                ],
            ],
            'labels' => ['Tersedia', 'Terisi', 'Pembersihan', 'Perawatan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
