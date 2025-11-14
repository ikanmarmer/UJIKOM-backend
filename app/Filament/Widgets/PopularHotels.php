<?php

namespace App\Filament\Widgets;

use App\Models\Hotel;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularHotels extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Hotel Terpopuler Berdasarkan Pemesanan')
            ->query(
                Hotel::query()
                    ->withCount('bookings')
                    ->with(['city'])
                    ->where('status', 'active')
                    ->orderBy('bookings_count', 'desc')
                    ->limit(10)
            )
            ->columns([
                // ✅ Kolom Ranking
                TextColumn::make('rank')
                    ->label('Peringkat')
                    ->getStateUsing(fn($record, $loop) => $loop->iteration)
                    ->alignCenter()
                    ->rowIndex()
                    ->width('50px')
                    ->color('gray')
                    ->weight('bold'),

                ImageColumn::make('thumbnail')
                    ->label('Gambar')
                    ->disk('public')
                    ->defaultImageUrl(url('storage/images/hotel-placeholder.png'))
                    ->circular(),

                TextColumn::make('name')
                    ->label('Nama Hotel')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn($record) => $record->city->name ?? ''),

                TextColumn::make('city.name')
                    ->label('Kota')
                    ->icon('heroicon-m-map-pin')
                    ->badge()
                    ->color('info'),

                TextColumn::make('star_rating')
                    ->label('Bintang')
                    ->formatStateUsing(fn($state) => str_repeat('⭐', $state)),

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
                    ->icon('heroicon-m-star'),

                TextColumn::make('bookings_count')
                    ->label('Total Pemesanan')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('total_reviews')
                    ->label('Ulasan')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-chat-bubble-left-right'),

                TextColumn::make('revenue')
                    ->label('Total Pendapatan')
                    ->getStateUsing(
                        fn($record) =>
                        $record->bookings()->where('payment_status', 'paid')->sum('total_price')
                    )
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn(Hotel $record): string => route('filament.admin.resources.hotels.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
