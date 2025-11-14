<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Actions\Action;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemesanan')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('booking_code')
                                    ->label('Kode Booking')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-ticket')
                                    ->copyable()
                                    ->columnSpan(1),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->columnSpan(1),

                                TextEntry::make('payment_status')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'unpaid' => 'warning',
                                        'refunded' => 'danger',
                                        default => 'gray',
                                    })
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->headerActions([
                        Action::make('download_invoice')
                            ->label('Download Invoice')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->url(fn($record) => route('resepsionis.bookings.invoice.download', $record->id))
                            ->openUrlInNewTab(),

                        Action::make('print_invoice')
                            ->label('Cetak Invoice')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->url(fn($record) => route('resepsionis.bookings.invoice.print', $record->id))
                            ->openUrlInNewTab(),
                    ]),

                Section::make('Detail Hotel & Kamar')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
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

                                TextEntry::make('hotel.address')
                                    ->label('Alamat Hotel')
                                    ->icon('heroicon-m-map-pin')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Informasi Tamu')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('guest_name')
                                    ->label('Nama Tamu')
                                    ->icon('heroicon-m-user')
                                    ->size('lg')
                                    ->weight('medium'),

                                TextEntry::make('guest_email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('user.name')
                                    ->label('Akun Terdaftar')
                                    ->icon('heroicon-m-user-circle')
                                    ->default('Pemesanan Tamu (Walk-in)')
                                    ->placeholder('Tidak ada'),

                                TextEntry::make('user.phone')
                                    ->label('Telepon')
                                    ->icon('heroicon-m-phone')
                                    ->default('Tidak ada')
                                    ->copyable(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Detail Pemesanan')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('check_in_date')
                                    ->label('Check-in')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('check_out_date')
                                    ->label('Check-out')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color('danger'),

                                TextEntry::make('nights')
                                    ->label('Durasi')
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
                    ])
                    ->collapsible(),

                Section::make('Kamar yang Ditugaskan')
                    ->schema([
                        TextEntry::make('rooms_list')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                if ($record->rooms->isEmpty()) {
                                    return '_Kamar belum ditugaskan. Kamar akan otomatis ditugaskan saat check-in._';
                                }

                                return $record->rooms
                                    ->groupBy('floor')
                                    ->map(function ($rooms, $floor) {
                                        $roomDetails = $rooms->map(function ($room) {
                                            $statusIcons = [
                                                'available' => '🟢',
                                                'occupied' => '🔴',
                                                'maintenance' => '🔧',
                                                'cleaning' => '🧹',
                                            ];
                                            $icon = $statusIcons[$room->status] ?? '⚪';
                                            $guests = $room->pivot->guests_count ?? '-';
                                            return "{$icon} **{$room->room_number}** (Status: {$room->status}, Tamu: {$guests})";
                                        })->join("\n");

                                        return "### Lantai {$floor}\n{$roomDetails}";
                                    })
                                    ->join("\n\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Informasi Pembayaran')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('price_per_night')
                                    ->label('Harga per Malam')
                                    ->money('IDR')
                                    ->weight('medium'),

                                TextEntry::make('total_price')
                                    ->label('Total Pembayaran')
                                    ->money('IDR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('payment.payment_type')
                                    ->label('Jenis Pembayaran')
                                    ->default('Belum dibayar')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                    ->visible(fn($record) => $record->payment !== null),

                                TextEntry::make('payment.midtrans_payment_type')
                                    ->label('Metode Pembayaran')
                                    ->default('Tidak ada')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                    ->visible(fn($record) => $record->payment?->midtrans_payment_type),

                                TextEntry::make('payment.midtrans_transaction_id')
                                    ->label('ID Transaksi')
                                    ->default('Tidak ada')
                                    ->copyable()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => $record->payment?->midtrans_transaction_id),

                                TextEntry::make('payment.paid_at')
                                    ->label('Dibayar Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-check-circle')
                                    ->default('Belum dibayar')
                                    ->visible(fn($record) => $record->payment?->paid_at),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Permintaan Khusus')
                    ->schema([
                        TextEntry::make('special_requests')
                            ->label('Catatan')
                            ->default('Tidak ada permintaan khusus')
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => empty($record->special_requests)),

                Section::make('Riwayat & Timeline')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dipesan Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-clock'),

                                TextEntry::make('expires_at')
                                    ->label('Pembayaran Kadaluarsa')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-exclamation-triangle')
                                    ->color('warning')
                                    ->visible(fn($record) => $record->status === 'pending' && $record->expires_at),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
