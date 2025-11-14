<?php

namespace App\Filament\Owner\Resources\RoomCategories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
class RoomCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Kategori Kamar')
                    ->schema([
                        // Thumbnail
                        Section::make('Gambar Kategori')
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

                        // Basic Information
                        Section::make('Informasi Dasar')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Kategori')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->icon('heroicon-m-squares-2x2')
                                            ->columnSpan(1),

                                        TextEntry::make('hotel.name')
                                            ->label('Hotel')
                                            ->icon('heroicon-m-building-office-2')
                                            ->badge()
                                            ->color('info')
                                            ->columnSpan(1),

                                        TextEntry::make('type')
                                            ->label('Tipe Kamar')
                                            ->badge()
                                            ->color('primary')
                                            ->formatStateUsing(fn($state) => ucfirst($state))
                                            ->columnSpan(1),

                                        TextEntry::make('max_guests')
                                            ->label('Maksimum Tamu')
                                            ->icon('heroicon-m-user-group')
                                            ->suffix(' tamu')
                                            ->badge()
                                            ->color('success')
                                            ->columnSpan(1),

                                        TextEntry::make('size')
                                            ->label('Ukuran Kamar')
                                            ->suffix(' m²')
                                            ->icon('heroicon-m-arrows-pointing-out')
                                            ->placeholder('Tidak ditentukan')
                                            ->columnSpan(1),

                                        TextEntry::make('price_per_night')
                                            ->label('Harga per Malam')
                                            ->money('IDR')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->color('success')
                                            ->icon('heroicon-m-currency-dollar')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        // Description
                        Section::make('Deskripsi')
                            ->schema([
                                Group::make([
                                    TextEntry::make('description')
                                        ->label('Deskripsi Kamar')
                                        ->prose()
                                        ->placeholder('Tidak ada deskripsi')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        // Room Statistics
                        Section::make('Statistik Kamar')
                            ->schema([
                                Grid::make()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('total_rooms')
                                            ->label('Total Kamar')
                                            ->icon('heroicon-m-home')
                                            ->badge()
                                            ->color('primary')
                                            ->size('lg')
                                            ->columnSpan(1),

                                        TextEntry::make('available_rooms_count')
                                            ->label('Kamar Tersedia')
                                            ->icon('heroicon-m-check-circle')
                                            ->badge()
                                            ->color('success')
                                            ->size('lg')
                                            ->columnSpan(1),

                                        TextEntry::make('occupied_rooms')
                                            ->label('Kamar Terisi')
                                            ->getStateUsing(fn($record) => $record->rooms()->where('status', 'occupied')->count())
                                            ->icon('heroicon-m-lock-closed')
                                            ->badge()
                                            ->color('warning')
                                            ->size('lg')
                                            ->columnSpan(1),

                                        TextEntry::make('maintenance_rooms')
                                            ->label('Dalam Perawatan')
                                            ->getStateUsing(fn($record) => $record->rooms()->where('status', 'maintenance')->count())
                                            ->icon('heroicon-m-wrench')
                                            ->badge()
                                            ->color('danger')
                                            ->size('lg')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        // Amenities
                        Section::make('Fasilitas Kamar')
                            ->schema([
                                Group::make([
                                    TextEntry::make('amenities.name')
                                        ->label('')
                                        ->badge()
                                        ->separator(',')
                                        ->color('gray')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible()
                            ->visible(fn($record) => $record->amenities->isNotEmpty()),

                        // Room Gallery
                        Section::make('Galeri Gambar')
                            ->schema([
                                Group::make([
                                    ImageEntry::make('image_urls')
                                        ->label('Foto Kamar')
                                        ->placeholder('Tidak ada gambar')
                                        ->disk('public')
                                        ->limit(5)
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->collapsible(),

                        // Rooms List
                        Section::make('Kamar Fisik')
                            ->schema([
                                Grid::make()
                                    ->columns(1)
                                    ->schema([
                                        TextEntry::make('rooms_list')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                return $record->rooms()
                                                    ->orderBy('floor')
                                                    ->orderBy('room_number')
                                                    ->get()
                                                    ->groupBy('floor')
                                                    ->map(function ($rooms, $floor) {
                                                        $roomList = $rooms->map(function ($room) {
                                                            $statusColors = [
                                                                'available' => '🟢',
                                                                'occupied' => '🔴',
                                                                'maintenance' => '🔧',
                                                                'cleaning' => '🧹',
                                                            ];
                                                            $statusIcon = $statusColors[$room->status] ?? '⚪';
                                                            return "{$statusIcon} {$room->room_number} ({$room->status})";
                                                        })->join(', ');

                                                        return "**Lantai {$floor}**: {$roomList}";
                                                    })
                                                    ->join("\n\n");
                                            })
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->description('Semua kamar fisik dalam kategori ini dikelompokkan berdasarkan lantai')
                            ->collapsible(),

                        // Timestamps
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
                                            ->label('Terakhir Diperbarui')
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
