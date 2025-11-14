<?php

namespace App\Filament\Owner\Resources\Reviews\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pengulas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->user->email),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office-2')
                    ->limit(30),

                TextColumn::make('rating')
                    ->label('Penilaian')
                    ->formatStateUsing(fn($state) => str_repeat('⭐', $state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('comment')
                    ->label('Komentar')
                    ->formatStateUsing(function ($state) {
                        if (!$state)
                            return '-';

                        // Batasi teks yang ditampilkan di kolom
                        return strlen($state) > 12
                            ? mb_substr($state, 0, 12) . '...'
                            : $state;
                    })
                    ->tooltip(function ($state) {
                        if (!$state)
                            return '-';

                        // Batasi tooltip maksimal 50 karakter
                        return strlen($state) > 50
                            ? mb_substr($state, 0, 50) . '...'
                            : $state;
                    })
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                    
                ImageColumn::make('images')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText()
                    ->toggleable(),

                TextColumn::make('booking.booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-ticket')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dikirim Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label('Penilaian')
                    ->options([
                        5 => '5 Bintang',
                        4 => '4 Bintang',
                        3 => '3 Bintang',
                        2 => '2 Bintang',
                        1 => '1 Bintang',
                    ])
                    ->native(false),

                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('has_images')
                    ->label('Dengan Foto')
                    ->query(fn($query) => $query->whereNotNull('images')->where('images', '!=', '[]')),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Dari'),
                        DatePicker::make('created_until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
