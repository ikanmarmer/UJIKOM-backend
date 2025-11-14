<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Ringkasan Pendapatan';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30days';

    protected function getData(): array
    {
        $data = $this->getRevenueData();

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (IDR)',
                    'data' => $data['revenue'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
                [
                    'label' => 'Pemesanan',
                    'data' => $data['bookings'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => true,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan (IDR)',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Pemesanan',
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

    private function getRevenueData(): array
    {
        $days = match ($this->filter) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 30,
        };

        $labels = [];
        $revenue = [];
        $bookings = [];

        $startDate = now()->subDays($days);

        if ($days <= 30) {
            // Daily data for short periods
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->format('d M');

                $dayRevenue = Booking::whereDate('created_at', $date)
                    ->where('payment_status', 'paid')
                    ->sum('total_price');

                $dayBookings = Booking::whereDate('created_at', $date)->count();

                $revenue[] = $dayRevenue;
                $bookings[] = $dayBookings;
            }
        } else {
            // Weekly data for longer periods
            $weeks = ceil($days / 7);
            for ($i = 0; $i < $weeks; $i++) {
                $weekStart = $startDate->copy()->addWeeks($i);
                $weekEnd = $weekStart->copy()->addWeek();

                $labels[] = $weekStart->format('d M');

                $weekRevenue = Booking::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->where('payment_status', 'paid')
                    ->sum('total_price');

                $weekBookings = Booking::whereBetween('created_at', [$weekStart, $weekEnd])->count();

                $revenue[] = $weekRevenue;
                $bookings[] = $weekBookings;
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'bookings' => $bookings,
        ];
    }
}
