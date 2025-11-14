<?php

namespace App\Filament\Owner\Resources\Hotels\Schemas;

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
                Section::make('Informasi Hotel')
                    ->schema([
                        Section::make('Gambar Utama')
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

                        Section::make('Informasi Dasar')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Hotel')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->icon('heroicon-m-building-office-2'),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                HotelStatus::ACTIVE => 'success',
                                                HotelStatus::INACTIVE => 'danger',
                                                HotelStatus::PENDING => 'warning',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('slug')
                                            ->label('Slug')
                                            ->icon('heroicon-m-link')
                                            ->copyable(),

                                        TextEntry::make('star_rating')
                                            ->label('Rating Bintang')
                                            ->formatStateUsing(fn($state) => str_repeat('⭐', $state)),

                                        TextEntry::make('city.name')
                                            ->label('City')
                                            ->icon('heroicon-m-map-pin')
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('city.province')
                                            ->label('Provinsi')
                                            ->icon('heroicon-m-map'),
                                    ]),
                            ])
                            ->collapsible(),

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
                                            ->copyable(),

                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable(),
                                    ]),
                            ])
                            ->collapsible(),

                        Section::make('Deskripsi')
                            ->schema([
                                Group::make([
                                    TextEntry::make('description')
                                        ->label('Deskripsi Hotel')
                                        ->prose()
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Statistik')
                            ->schema([
                                Grid::make()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('average_rating')
                                            ->label('Rating Rata-rata')
                                            ->formatStateUsing(fn($state) => number_format($state, 1) . ' / 5.0')
                                            ->icon('heroicon-m-star')
                                            ->badge()
                                            ->color('warning'),

                                        TextEntry::make('total_reviews')
                                            ->label('Total Ulasan')
                                            ->icon('heroicon-m-chat-bubble-left-right')
                                            ->badge()
                                            ->color('info'),

                                        TextEntry::make('roomCategories_count')
                                            ->label('Kategori Kamar')
                                            ->getStateUsing(fn($record) => $record->roomCategories()->count())
                                            ->icon('heroicon-m-squares-2x2')
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('total_rooms')
                                            ->label('Total Kamar')
                                            ->getStateUsing(fn($record) => $record->roomCategories()->sum('total_rooms'))
                                            ->icon('heroicon-m-home')
                                            ->badge()
                                            ->color('primary'),
                                    ]),
                            ])
                            ->collapsible(),

                        Section::make('Fasilitas')
                            ->schema([
                                Group::make([
                                    TextEntry::make('amenities.name')
                                        ->label('')
                                        ->badge()
                                        ->separator(',')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Galeri Gambar')
                            ->schema([
                                Group::make([
                                    ImageEntry::make('image_urls')
                                        ->label('Foto Hotel')
                                        ->placeholder('Tidak ada gambar')
                                        ->disk('public')
                                        ->limit(5)
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Informasi Tambahan')
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
                            ->collapsible(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
