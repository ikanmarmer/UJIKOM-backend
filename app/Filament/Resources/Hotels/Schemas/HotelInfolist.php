<?php

namespace App\Filament\Resources\Hotels\Schemas;

use App\Enums\HotelStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class HotelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Lengkap Hotel')
                    ->schema([
                        // Header dengan gambar utama
                        Section::make('Gambar Utama Hotel')
                            ->schema([
                                ImageEntry::make('thumbnail')
                                    ->label('')
                                    ->defaultImageUrl(url('storage/images/image-placeholder.png'))
                                    ->disk('public')
                                    ->height(200)
                                    ->width(400)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),

                        // Informasi Dasar Hotel
                        Section::make('Informasi Dasar Hotel')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Hotel')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->icon('heroicon-m-building-office-2')
                                            ->columnSpan(1),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                HotelStatus::ACTIVE => 'success',
                                                HotelStatus::INACTIVE => 'danger',
                                                HotelStatus::PENDING => 'warning',
                                                default => 'gray',
                                            })
                                            ->columnSpan(1),

                                        TextEntry::make('slug')
                                            ->label('Slug')
                                            ->icon('heroicon-m-link')
                                            ->copyable()
                                            ->columnSpan(1),

                                        TextEntry::make('star_rating')
                                            ->label('Rating Bintang')
                                            ->formatStateUsing(fn($state) => $state ? str_repeat('⭐', $state) : 'Belum ada rating')
                                            ->columnSpan(1),

                                        TextEntry::make('owner.name')
                                            ->label('Pemilik')
                                            ->icon('heroicon-m-user')
                                            ->badge()
                                            ->color('info')
                                            ->columnSpan(1),

                                        TextEntry::make('city.name')
                                            ->label('Kota')
                                            ->icon('heroicon-m-map-pin')
                                            ->badge()
                                            ->color('success')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        // Informasi Kontak
                        Section::make('Informasi Kontak')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('address')
                                            ->label('Alamat Lengkap')
                                            ->icon('heroicon-m-map')
                                            ->columnSpanFull(),

                                        TextEntry::make('phone')
                                            ->label('Telepon')
                                            ->icon('heroicon-m-phone')
                                            ->copyable()
                                            ->columnSpan(1),

                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable()
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        // Deskripsi Hotel
                        Section::make('Deskripsi Hotel')
                            ->schema([
                                Group::make([
                                    TextEntry::make('isi deskripsi')
                                        ->label('')
                                        ->html()
                                        ->placeholder('Tidak ada deskripsi')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        // Statistik
                        Section::make('Statistik Hotel')
                            ->schema([
                                Grid::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('average_rating')
                                            ->label('Rating Rata-rata')
                                            ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . ' / 5.0' : 'Belum ada rating')
                                            ->icon('heroicon-m-star')
                                            ->badge()
                                            ->color('warning')
                                            ->columnSpan(1),

                                        TextEntry::make('total_reviews')
                                            ->label('Total Ulasan')
                                            ->icon('heroicon-m-chat-bubble-left-right')
                                            ->badge()
                                            ->color('info')
                                            ->columnSpan(1),

                                        TextEntry::make('roomCategories_count')
                                            ->label('Kategori Kamar')
                                            ->getStateUsing(fn($record) => $record->roomCategories()->count())
                                            ->icon('heroicon-m-squares-2x2')
                                            ->badge()
                                            ->color('success')
                                            ->columnSpan(1),

                                        TextEntry::make('total_rooms')
                                            ->label('Total Kamar')
                                            ->getStateUsing(fn($record) => $record->roomCategories()->sum('total_rooms'))
                                            ->icon('heroicon-m-home')
                                            ->badge()
                                            ->color('primary')
                                            ->columnSpan(1),

                                        TextEntry::make('bookings_count')
                                            ->label('Total Pemesanan')
                                            ->getStateUsing(fn($record) => $record->bookings()->count())
                                            ->icon('heroicon-m-calendar')
                                            ->badge()
                                            ->color('gray')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        // Fasilitas
                        Section::make('Fasilitas Hotel')
                            ->schema([
                                Group::make([
                                    TextEntry::make('amenities.name')
                                        ->label('nama')
                                        ->badge()
                                        ->separator(',')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        // Galeri Foto
                        Section::make('Galeri Foto Hotel')
                            ->schema([
                                Group::make([
                                    ImageEntry::make('image_urls')
                                        ->label('Foto-Foto Hotel')
                                        ->placeholder('Belum ada gambar hotel')
                                        ->disk('public')
                                        ->limit(5)
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        // Informasi Timestamp
                        Section::make('Informasi Tambahan')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->dateTime('d M Y, H:i')
                                            ->icon('heroicon-m-calendar')
                                            ->color('gray')
                                            ->columnSpan(1),

                                        TextEntry::make('updated_at')
                                            ->label('Diperbarui Pada')
                                            ->dateTime('d M Y, H:i')
                                            ->icon('heroicon-m-clock')
                                            ->color('gray')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(false),
            ]);
    }
}
