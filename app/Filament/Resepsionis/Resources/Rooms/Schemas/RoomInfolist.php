<?php

namespace App\Filament\Resepsionis\Resources\Rooms\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RoomInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kamar')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('room_number')
                                    ->label('Nomor Kamar')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->icon('heroicon-m-home')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('floor')
                                    ->label('Lantai')
                                    ->icon('heroicon-m-building-office')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('status')
                                    ->label('Status')
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
                                    }),
                            ]),
                    ]),

                Section::make('Kategori Kamar')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('roomCategory.hotel.name')
                                    ->label('Hotel')
                                    ->icon('heroicon-m-building-office-2')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('roomCategory.name')
                                    ->label('Kategori')
                                    ->icon('heroicon-m-squares-2x2')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('roomCategory.type')
                                    ->label('Tipe Kamar')
                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                    ->badge(),

                                TextEntry::make('roomCategory.max_guests')
                                    ->label('Kapasitas')
                                    ->suffix(' tamu')
                                    ->icon('heroicon-m-user-group')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('roomCategory.price_per_night')
                                    ->label('Harga per Malam')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('roomCategory.size')
                                    ->label('Ukuran')
                                    ->suffix(' m²')
                                    ->placeholder('Tidak ditentukan'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Gambar Kamar')
                    ->schema([
                        ImageEntry::make('category_image_urls')
                            ->label('')
                            ->disk('public')
                            ->defaultImageUrl(url('storage/images/image-placeholder.png'))
                            ->limit(5)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn($record) => !empty($record->category_image_urls)),

                Section::make('Fasilitas Kamar')
                    ->schema([
                        TextEntry::make('roomCategory.amenities.name')
                            ->label('')
                            ->badge()
                            ->separator(',')
                            ->color('gray')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn($record) => $record->roomCategory->amenities->isNotEmpty()),

                Section::make('Booking Aktif')
                    ->schema([
                        TextEntry::make('current_booking')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $currentBooking = $record->bookings()
                                    ->whereIn('status', ['confirmed', 'active'])
                                    ->whereDate('booking_room.check_in_date', '<=', today())
                                    ->whereDate('booking_room.check_out_date', '>=', today())
                                    ->with(['user', 'hotel'])
                                    ->first();

                                if (!$currentBooking) {
                                    return '_Tidak ada booking aktif_';
                                }

                                $checkIn = \Carbon\Carbon::parse($currentBooking->pivot->check_in_date)->format('d M Y');
                                $checkOut = \Carbon\Carbon::parse($currentBooking->pivot->check_out_date)->format('d M Y');
                                $guests = $currentBooking->pivot->guests_count ?? 0;

                                return "**Booking Code:** {$currentBooking->booking_code}\n\n" .
                                    "**Tamu:** {$currentBooking->guest_name} ({$currentBooking->guest_email})\n\n" .
                                    "**Periode:** {$checkIn} - {$checkOut}\n\n" .
                                    "**Jumlah Tamu:** {$guests} tamu\n\n" .
                                    "**Status:** " . ucfirst($currentBooking->status);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => $record->status === 'available'),

                Section::make('Riwayat')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-clock'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
