<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-ticket'),

                TextColumn::make('guest_name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->guest_email),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->icon('heroicon-m-building-office-2')
                    ->description(fn ($record) => $record->roomCategory->name),

                TextColumn::make('check_in_date')
                    ->label('Tanggal Check-in')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('check_out_date')
                    ->label('Tanggal Check-out')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('nights')
                    ->label('Malam')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->suffix(' malam'),

                TextColumn::make('number_of_rooms')
                    ->label('Kamar')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-home'),

                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dipesan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'confirmed' => 'Terkonfirmasi',
                        'active' => 'Aktif',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false),

                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Lunas',
                        'refunded' => 'Dikembalikan',
                    ])
                    ->native(false),

                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('check_in_date')
                    ->form([
                        DatePicker::make('check_in_from')
                            ->label('Check-in Dari'),
                        DatePicker::make('check_in_until')
                            ->label('Check-in Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['check_in_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '>=', $date),
                            )
                            ->when(
                                $data['check_in_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '<=', $date),
                            );
                    }),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dipesan Dari'),
                        DatePicker::make('created_until')
                            ->label('Dipesan Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Filter::make('total_price')
                    ->form([
                        TextInput::make('price_from')
                            ->prefix('IDR'),
                        TextInput::make('price_to')
                            ->prefix('IDR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('total_price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('total_price', '<=', $price),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending' && $record->payment_status === 'paid')
                    ->action(fn ($record) => $record->update(['status' => 'confirmed'])),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);

                        // Release rooms
                        foreach ($record->rooms as $room) {
                            if ($room->status === 'occupied') {
                                $room->update(['status' => 'available']);
                            }
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
