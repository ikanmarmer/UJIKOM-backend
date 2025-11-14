<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kota')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('Gambar Kota')
                            ->defaultImageUrl(url('storage/images/image-placeholder.png'))
                            ->disk('public')
                            ->height(300)
                            ->width(400)
                            ->extraAttributes([
                                'class' => 'border border-gray-200',
                            ])
                            ->extraImgAttributes([
                                'class' => 'w-full h-full max-w-md mx-auto rounded-lg object-cover shadow-md' ,
                                'alt' => 'Foto Produk',
                            ])
                            ->columnSpanFull(),

                        TextEntry::make('name')
                            ->label('Nama Kota')
                            ->size('lg')
                            ->weight('bold')
                            ->icon('heroicon-m-map-pin'),

                        TextEntry::make('province')
                            ->label('Provinsi')
                            ->icon('heroicon-m-map')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('slug')
                            ->icon('heroicon-m-link')
                            ->copyable()
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(3),

                Section::make('Statistik')
                    ->schema([
                        TextEntry::make('hotels_count')
                            ->label('Total Hotel')
                            ->getStateUsing(fn($record) => $record->hotels()->count())
                            ->icon('heroicon-m-building-office-2')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('active_hotels_count')
                            ->label('Hotel Aktif')
                            ->getStateUsing(fn($record) => $record->hotels()->where('status', 'active')->count())
                            ->icon('heroicon-m-check-circle')
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),
            ]);
    }
}
