<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Booking;
use App\Models\Hotel;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Pendapatan per Hotel';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30days';

    protected function getData(): array
    {
        $ownerId = auth()->id();
        $hotels = Hotel::where('owner_id', $ownerId)->get();

        $days = match ($this->filter) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 30,
        };

        $startDate = now()->subDays($days);
        $labels = [];
        $datasets = [];

        // Generate labels based on period
        if ($days <= 30) {
            // Daily labels
            for ($i = 0; $i < $days; $i++) {
                $labels[] = $startDate->copy()->addDays($i)->format('d M');
            }
        } else {
            // Weekly labels
            $weeks = ceil($days / 7);
            for ($i = 0; $i < $weeks; $i++) {
                $labels[] = $startDate->copy()->addWeeks($i)->format('d M');
            }
        }

        // Generate dataset for each hotel
        $colors = [
            'rgb(59, 130, 246)',    // blue
            'rgb(16, 185, 129)',    // green
            'rgb(245, 158, 11)',    // yellow
            'rgb(239, 68, 68)',     // red
            'rgb(139, 92, 246)',    // purple
            'rgb(236, 72, 153)',    // pink
        ];

        foreach ($hotels as $index => $hotel) {
            $data = [];

            if ($days <= 30) {
                // Daily data
                for ($i = 0; $i < $days; $i++) {
                    $date = $startDate->copy()->addDays($i);
                    $revenue = Booking::where('hotel_id', $hotel->id)
                        ->whereDate('created_at', $date)
                        ->where('payment_status', 'paid')
                        ->sum('total_price');
                    $data[] = $revenue;
                }
            } else {
                // Weekly data
                $weeks = ceil($days / 7);
                for ($i = 0; $i < $weeks; $i++) {
                    $weekStart = $startDate->copy()->addWeeks($i);
                    $weekEnd = $weekStart->copy()->addWeek();
                    $revenue = Booking::where('hotel_id', $hotel->id)
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->where('payment_status', 'paid')
                        ->sum('total_price');
                    $data[] = $revenue;
                }
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $hotel->name,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => str_replace('rgb', 'rgba', str_replace(')', ', 0.1)', $color)),
                'fill' => true,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan (IDR)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => '7 Hari Terakhir',
            '30days' => '30 Hari Terakhir',
            '90days' => '3 Bulan Terakhir',
            'year' => 'Tahun Ini',
        ];
    }
}
