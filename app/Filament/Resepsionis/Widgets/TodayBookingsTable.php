<?php

namespace App\Filament\Resepsionis\Widgets;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayBookingsTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Check-in & Check-out Hari Ini')
            ->query(
                Booking::query()
                    ->whereHas('hotel.staff', function ($query) {
                        $query->where('user_id', auth()->id())
                            ->where('is_active', true);
                    })
                    ->where(function ($query) {
                        $query->whereDate('check_in_date', today())
                            ->orWhereDate('check_out_date', today());
                    })
                    ->whereIn('status', ['confirmed', 'active'])
                    ->with(['hotel', 'roomCategory'])
            )
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('guest_name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->description(fn($record) => $record->guest_email),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->limit(20),

                TextColumn::make('roomCategory.name')
                    ->label('Kategori')
                    ->limit(15),

                TextColumn::make('action_type')
                    ->label('Aksi')
                    ->getStateUsing(function ($record) {
                        if ($record->check_in_date->isToday()) {
                            return 'Check-in';
                        }
                        if ($record->check_out_date->isToday()) {
                            return 'Check-out';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Check-in' => 'success',
                        'Check-out' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'confirmed' => 'info',
                        'active' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('number_of_rooms')
                    ->label('Kamar')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Action::make('checkin')
                    ->label('Check-in')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->url(fn($record) => route('filament.resepsionis.resources.bookings.checkin', $record))
                    ->visible(
                        fn($record) =>
                        $record->status === 'confirmed' &&
                        $record->check_in_date->isToday()
                    ),

                Action::make('checkout')
                    ->label('Check-out')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('warning')
                    ->url(fn($record) => route('filament.resepsionis.resources.bookings.checkout', $record))
                    ->visible(
                        fn($record) =>
                        $record->status === 'active' &&
                        $record->check_out_date->isToday()
                    ),

                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => route('filament.resepsionis.resources.bookings.view', $record)),
            ])
            ->defaultSort('check_in_date', 'asc');
    }
}
