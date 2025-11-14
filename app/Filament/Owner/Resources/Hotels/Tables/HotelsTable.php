<?php

namespace App\Filament\Owner\Resources\Hotels\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HotelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Gambar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('storage/images/hotel-placeholder.png')),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->city->name ?? ''),

                TextColumn::make('city.name')
                    ->label('Kota')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-map-pin')
                    ->badge()
                    ->color('info'),

                TextColumn::make('star_rating')
                    ->label('Bintang')
                    ->formatStateUsing(fn($state) => str_repeat('⭐', $state))
                    ->sortable(),

                TextColumn::make('average_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn($state) => number_format($state, 1))
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'info',
                        $state >= 3.0 => 'warning',
                        default => 'danger',
                    })
                    ->icon('heroicon-m-star')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('room_categories_count')
                    ->counts('roomCategories')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-squares-2x2'),

                TextColumn::make('total_rooms')
                    ->label('Kamar')
                    ->getStateUsing(fn($record) => $record->roomCategories()->sum('total_rooms'))
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-home'),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->label('Kota')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'pending' => 'Menunggu',
                    ])
                    ->native(false),

                SelectFilter::make('star_rating')
                    ->options([
                        1 => '1 Bintang',
                        2 => '2 Bintang',
                        3 => '3 Bintang',
                        4 => '4 Bintang',
                        5 => '5 Bintang',
                    ])
                    ->native(false),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
