<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemesanan')
                    ->schema([
                        TextEntry::make('booking_code')
                            ->label('Kode Booking')
                            ->size('lg')
                            ->weight('bold')
                            ->icon('heroicon-m-ticket')
                            ->copyable()
                            ->badge()
                            ->color('primary')
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->size('lg')
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'active' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pending' => 'Menunggu',
                                'confirmed' => 'Terkonfirmasi',
                                'active' => 'Aktif',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => $state,
                            }),

                        TextEntry::make('payment_status')
                            ->label('Status Pembayaran')
                            ->badge()
                            ->size('lg')
                            ->color(fn(string $state): string => match ($state) {
                                'paid' => 'success',
                                'unpaid' => 'warning',
                                'refunded' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'paid' => 'Lunas',
                                'unpaid' => 'Belum Bayar',
                                'refunded' => 'Dikembalikan',
                                default => $state,
                            }),
                    ])
                    ->columns(3),

                Section::make('Informasi Tamu')
                    ->schema([
                        TextEntry::make('guest_name')
                            ->label('Nama Tamu')
                            ->icon('heroicon-m-user')
                            ->size('lg'),

                        TextEntry::make('guest_email')
                            ->label('Email Tamu')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('user.phone')
                            ->label('Telepon')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->placeholder('Tidak tersedia'),

                        TextEntry::make('total_guests')
                            ->label('Jumlah Tamu')
                            ->icon('heroicon-m-users')
                            ->badge()
                            ->suffix(' tamu'),
                    ])
                    ->columns(2),

                Section::make('Detail Hotel & Kamar')
                    ->schema([
                        TextEntry::make('hotel.name')
                            ->label('Hotel')
                            ->icon('heroicon-m-building-office-2')
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('hotel.address')
                            ->label('Alamat')
                            ->icon('heroicon-m-map-pin'),

                        TextEntry::make('roomCategory.name')
                            ->label('Kategori Kamar')
                            ->icon('heroicon-m-home')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('number_of_rooms')
                            ->label('Jumlah Kamar')
                            ->icon('heroicon-m-squares-2x2')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('guests_per_room')
                            ->label('Tamu per Kamar')
                            ->icon('heroicon-m-user-group')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Kamar yang Dipesan')
                    ->schema([
                        TextEntry::make('rooms_list')
                            ->label('Nomor Kamar')
                            ->getStateUsing(
                                fn($record) =>
                                $record->rooms->map(fn($room) => $room->room_number)->join(', ')
                            )
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Detail Menginap')
                    ->schema([
                        TextEntry::make('check_in_date')
                            ->label('Tanggal Check-in')
                            ->date('l, d F Y')
                            ->icon('heroicon-m-calendar'),

                        TextEntry::make('check_out_date')
                            ->label('Tanggal Check-out')
                            ->date('l, d F Y')
                            ->icon('heroicon-m-calendar'),

                        TextEntry::make('nights')
                            ->label('Malam')
                            ->icon('heroicon-m-moon')
                            ->badge()
                            ->suffix(' malam')
                            ->color('info'),
                    ])
                    ->columns(3),

                Section::make('Harga')
                    ->schema([
                        TextEntry::make('price_per_night')
                            ->label('Harga per Malam')
                            ->money('IDR')
                            ->icon('heroicon-m-banknotes'),

                        TextEntry::make('total_price')
                            ->label('Total Harga')
                            ->money('IDR')
                            ->size('lg')
                            ->weight('bold')
                            ->icon('heroicon-m-currency-dollar')
                            ->color('success'),
                    ])
                    ->columns(2),

                Section::make('Permintaan Khusus')
                    ->schema([
                        TextEntry::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->placeholder('Tidak ada permintaan khusus')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->visible(fn($record) => !empty($record->special_requests))
                    ->collapsible(),

                Section::make('Informasi Pembayaran')
                    ->schema([
                        TextEntry::make('payment.transaction_id')
                            ->label('ID Transaksi')
                            ->copyable()
                            ->placeholder('Belum ada pembayaran'),

                        TextEntry::make('payment.payment_type')
                            ->label('Metode Pembayaran')
                            ->badge()
                            ->placeholder('Tidak tersedia'),

                        TextEntry::make('payment.paid_at')
                            ->label('Dibayar Pada')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Belum dibayar'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->payment !== null)
                    ->collapsible(),

                Section::make('Catatan Waktu')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-calendar'),

                        TextEntry::make('expires_at')
                            ->label('Kadaluarsa Pada')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-clock')
                            ->placeholder('Tidak ada kadaluarsa'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-arrow-path'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
