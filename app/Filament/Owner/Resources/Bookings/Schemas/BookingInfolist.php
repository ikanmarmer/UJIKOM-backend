<?php

namespace App\Filament\Owner\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemesanan')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('booking_code')
                                    ->label('Kode Booking')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-ticket')
                                    ->copyable(),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('hotel.name')
                                    ->label('Hotel')
                                    ->icon('heroicon-m-building-office-2')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('roomCategory.name')
                                    ->label('Kategori Kamar')
                                    ->icon('heroicon-m-squares-2x2')
                                    ->badge()
                                    ->color('primary'),
                            ]),
                    ]),

                Section::make('Informasi Tamu')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('guest_name')
                                    ->label('Nama Tamu')
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('guest_email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('user.name')
                                    ->label('Pemilik Akun')
                                    ->icon('heroicon-m-user-circle')
                                    ->default('Pemesanan Tamu'),

                                TextEntry::make('user.phone')
                                    ->label('Telepon')
                                    ->icon('heroicon-m-phone')
                                    ->default('Tidak ada'),
                            ]),
                    ]),

                Section::make('Detail Pemesanan')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('check_in_date')
                                    ->label('Tanggal Check-in')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('check_out_date')
                                    ->label('Tanggal Check-out')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('nights')
                                    ->label('Malam')
                                    ->badge()
                                    ->color('gray')
                                    ->suffix(' malam'),

                                TextEntry::make('number_of_rooms')
                                    ->label('Jumlah Kamar')
                                    ->icon('heroicon-m-home')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('guests_per_room')
                                    ->label('Tamu per Kamar')
                                    ->icon('heroicon-m-user-group')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('total_guests')
                                    ->label('Total Tamu')
                                    ->icon('heroicon-m-users')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Kamar yang Ditugaskan')
                    ->schema([
                        TextEntry::make('rooms_list')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                return $record->rooms
                                    ->groupBy('floor')
                                    ->map(function ($rooms, $floor) {
                                        $roomNumbers = $rooms->pluck('room_number')->join(', ');
                                        return "**Lantai {$floor}**: {$roomNumbers}";
                                    })
                                    ->join("\n\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Pembayaran')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('price_per_night')
                                    ->label('Harga per Malam')
                                    ->money('IDR')
                                    ->weight('bold'),

                                TextEntry::make('total_price')
                                    ->label('Total Harga')
                                    ->money('IDR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('payment_status')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'unpaid' => 'warning',
                                        'refunded' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        TextEntry::make('payment.midtrans_payment_type')
                            ->label('Metode Pembayaran')
                            ->default('Tidak ada')
                            ->badge()
                            ->visible(fn($record) => $record->payment?->midtrans_payment_type),
                    ]),

                Section::make('Permintaan Khusus')
                    ->schema([
                        TextEntry::make('special_requests')
                            ->label('Permintaan')
                            ->default('Tidak ada permintaan khusus')
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Riwayat Pemesanan')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dipesan Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('expires_at')
                                    ->label('Pembayaran Kadaluarsa')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-clock')
                                    ->visible(fn($record) => $record->status === 'pending'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
