<?php

namespace App\Filament\Resepsionis\Resources\Rooms\Tables;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_number')
                    ->label('Nomor Kamar')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-home')
                    ->description(fn($record) => $record->roomCategory->name),

                TextColumn::make('roomCategory.hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('floor')
                    ->label('Lantai')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-building-office'),

                TextColumn::make('roomCategory.type')
                    ->label('Tipe')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('roomCategory.max_guests')
                    ->label('Kapasitas')
                    ->alignCenter()
                    ->suffix(' tamu')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('roomCategory.price_per_night')
                    ->label('Harga/Malam')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'maintenance' => 'warning',
                        'cleaning' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'occupied' => 'Terisi',
                        'maintenance' => 'Perawatan',
                        'cleaning' => 'Pembersihan',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('current_guest')
                    ->label('Tamu Saat Ini')
                    ->getStateUsing(function ($record) {
                        $booking = $record->bookings()
                            ->whereIn('status', ['confirmed', 'active'])
                            ->whereDate('booking_room.check_in_date', '<=', today())
                            ->whereDate('booking_room.check_out_date', '>=', today())
                            ->first();

                        return $booking ? $booking->guest_name : '-';
                    })
                    ->searchable(false)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Tersedia',
                        'occupied' => 'Terisi',
                        'maintenance' => 'Perawatan',
                        'cleaning' => 'Pembersihan',
                    ])
                    ->native(false),

                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('roomCategory.hotel', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('room_category_id')
                    ->label('Kategori Kamar')
                    ->relationship('roomCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('floor')
                    ->options([
                        'Ground' => 'Lantai Dasar',
                        '1' => 'Lantai 1',
                        '2' => 'Lantai 2',
                        '3' => 'Lantai 3',
                        '4' => 'Lantai 4',
                        '5' => 'Lantai 5',
                        '6' => 'Lantai 6',
                        '7' => 'Lantai 7',
                        '8' => 'Lantai 8',
                        '9' => 'Lantai 9',
                        '10' => 'Lantai 10',
                    ])
                    ->native(false),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('change_status')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->url(fn($record) => route('filament.resepsionis.resources.rooms.manage-status', $record))
                    ->visible(fn($record) => in_array($record->status, ['available', 'cleaning', 'maintenance'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('set_available')
                        ->label('Tandai Tersedia')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status !== 'occupied') {
                                    $record->update(['status' => 'available']);
                                }
                            });
                        }),

                    BulkAction::make('set_cleaning')
                        ->label('Tandai Pembersihan')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status !== 'occupied') {
                                    $record->update(['status' => 'cleaning']);
                                }
                            });
                        }),

                    BulkAction::make('set_maintenance')
                        ->label('Tandai Perawatan')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status !== 'occupied') {
                                    $record->update(['status' => 'maintenance']);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('room_number', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
