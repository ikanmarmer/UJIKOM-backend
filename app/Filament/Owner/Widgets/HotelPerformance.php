<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Hotel;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class HotelPerformance extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Performa Hotel')
            ->query(
                Hotel::query()
                    ->where('owner_id', auth()->id())
                    ->withCount(['bookings as total_bookings'])
                    ->withCount([
                        'bookings as completed_bookings' => fn($query) =>
                            $query->where('status', 'completed')
                    ])
                    ->withSum([
                        'bookings as total_revenue' => fn($query) =>
                            $query->where('payment_status', 'paid')
                    ], 'total_price')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-office-2'),

                TextColumn::make('city.name')
                    ->label('Kota')
                    ->badge()
                    ->color('info'),

                TextColumn::make('average_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn($state) => number_format($state, 1))
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'info',
                        $state >= 3.0 => 'warning',
                        default => 'danger',
                    })
                    ->icon('heroicon-m-star')
                    ->sortable(),

                TextColumn::make('total_bookings')
                    ->label('Total Pemesanan')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('completed_bookings')
                    ->label('Selesai')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('total_revenue')
                    ->label('Total Pendapatan')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success')
                    ->placeholder('Belum ada pendapatan')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('total_revenue', 'desc');
    }
}
