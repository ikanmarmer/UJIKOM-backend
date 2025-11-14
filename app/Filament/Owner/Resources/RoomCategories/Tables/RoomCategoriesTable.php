<?php

namespace App\Filament\Owner\Resources\RoomCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class RoomCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Gambar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('storage/images/image-placeholder.png')),
                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office-2')
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => ucfirst($record->type)),

                TextColumn::make('price_per_night')
                    ->label('Harga/Malam')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('max_guests')
                    ->label('Maks Tamu')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-user-group')
                    ->suffix(' tamu'),

                TextColumn::make('size')
                    ->label('Ukuran')
                    ->suffix(' m²')
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),

                TextColumn::make('total_rooms')
                    ->label('Total Kamar')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-home')
                    ->sortable(),

                TextColumn::make('available_rooms_count')
                    ->label('Tersedia')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->sortable(),

                TextColumn::make('occupied_rooms')
                    ->label('Terisi')
                    ->getStateUsing(fn($record) => $record->rooms()->where('status', 'occupied')->count())
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-lock-closed'),

                TextColumn::make('amenities_count')
                    ->counts('amenities')
                    ->label('Fasilitas')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('type')
                    ->label('Tipe Kamar')
                    ->options([
                        'single' => 'Single',
                        'double' => 'Double',
                        'twin' => 'Twin',
                        'suite' => 'Suite',
                        'deluxe' => 'Deluxe',
                        'standard' => 'Standard',
                    ])
                    ->native(false),

                Filter::make('price_range')
                    ->form([
                        TextInput::make('price_from')
                            ->label('Harga Minimum')
                            ->numeric()
                            ->prefix('IDR'),
                        TextInput::make('price_to')
                            ->label('Harga Maksimum')
                            ->numeric()
                            ->prefix('IDR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn(Builder $query, $price): Builder => $query->where('price_per_night', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn(Builder $query, $price): Builder => $query->where('price_per_night', '<=', $price),
                            );
                    }),

                Filter::make('has_availability')
                    ->label('Kamar Tersedia')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereHas('rooms', fn($q) => $q->where('status', 'available'))
                    ),

                Filter::make('max_guests')
                    ->form([
                        TextInput::make('min_guests')
                            ->label('Minimum Tamu')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['min_guests'],
                            fn(Builder $query, $guests): Builder => $query->where('max_guests', '>=', $guests),
                        );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kategori Kamar')
                    ->modalDescription('Apakah Anda yakin ingin menghapus kategori kamar ini? Semua kamar terkait juga akan dihapus.')
                    ->successNotificationTitle('Kategori kamar dihapus'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Kategori Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus kategori kamar yang dipilih? Semua kamar terkait juga akan dihapus.')
                        ->successNotificationTitle('Kategori kamar dihapus'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
