<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Booking;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBookings extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pemesanan Terbaru')
            ->query(
                Booking::query()
                    ->whereHas('hotel', function ($query) {
                        $query->where('owner_id', auth()->id());
                    })
                    ->with(['hotel', 'user', 'roomCategory'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode')
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-ticket'),

                TextColumn::make('guest_name')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->limit(25)
                    ->icon('heroicon-m-building-office-2'),

                TextColumn::make('check_in_date')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Dipesan')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn(Booking $record): string => route('filament.owner.resources.bookings.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
