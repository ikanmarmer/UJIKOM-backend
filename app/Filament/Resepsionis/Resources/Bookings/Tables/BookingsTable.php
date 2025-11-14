<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Tables;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                    ->description(fn($record) => $record->guest_email),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->icon('heroicon-m-building-office-2')
                    ->toggleable(),

                TextColumn::make('roomCategory.name')
                    ->label('Kategori')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),

                TextColumn::make('check_in_date')
                    ->label('Check-in')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('check_out_date')
                    ->label('Check-out')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->toggleable(),

                TextColumn::make('nights')
                    ->label('Malam')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->suffix(' malam')
                    ->toggleable(),

                TextColumn::make('number_of_rooms')
                    ->label('Kamar')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-home'),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
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
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dipesan')
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

                Filter::make('check_in_today')
                    ->label('Check-in Hari Ini')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereDate('check_in_date', today())
                    )
                    ->toggle(),

                Filter::make('check_out_today')
                    ->label('Check-out Hari Ini')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereDate('check_out_date', today())
                    )
                    ->toggle(),

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
                                fn(Builder $query, $date): Builder => $query->whereDate('check_in_date', '>=', $date),
                            )
                            ->when(
                                $data['check_in_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('check_in_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn($record) => in_array($record->status, ['pending', 'confirmed'])),

                Action::make('checkin')
                    ->label('Check-in')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Check-in Tamu')
                    ->modalDescription(fn($record) => "Proses check-in untuk {$record->guest_name}?")
                    ->action(function ($record) {
                        // Check-in logic akan dihandle di page
                        return redirect()->route('filament.resepsionis.resources.bookings.checkin', $record);
                    })
                    ->visible(
                        fn($record) =>
                        $record->status === 'confirmed' &&
                        $record->payment_status === 'paid' &&
                        today()->gte($record->check_in_date)
                    ),

                Action::make('checkout')
                    ->label('Check-out')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Check-out Tamu')
                    ->modalDescription(fn($record) => "Proses check-out untuk {$record->guest_name}?")
                    ->action(function ($record) {
                        return redirect()->route('filament.resepsionis.resources.bookings.checkout', $record);
                    })
                    ->visible(fn($record) => $record->status === 'active'),

                Action::make('download_invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn($record) => route('resepsionis.bookings.invoice.download', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->payment_status === 'paid'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_paid')
                        ->label('Tandai Lunas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->payment_status === 'unpaid') {
                                    $record->update([
                                        'payment_status' => 'paid',
                                        'status' => 'confirmed',
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if (in_array($record->status, ['pending', 'confirmed'])) {
                                    $record->update(['status' => 'cancelled']);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
