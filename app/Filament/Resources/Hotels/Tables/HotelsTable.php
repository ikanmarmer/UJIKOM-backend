<?php

namespace App\Filament\Resources\Hotels\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
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

                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),

                TextColumn::make('city.name')
                    ->label('Kota')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-map-pin')
                    ->toggleable(),

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

                TextColumn::make('total_reviews')
                    ->label('Ulasan')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('room_categories_count')
                    ->counts('roomCategories')
                    ->label('Kategori Kamar')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-home'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->label('Kota')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('owner_id')
                    ->label('Pemilik')
                    ->relationship('owner', 'name')
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

                Filter::make('average_rating')
                    ->form([
                        TextInput::make('rating_from')
                            ->numeric()
                            ->placeholder('Rating minimum'),
                        TextInput::make('rating_to')
                            ->numeric()
                            ->placeholder('Rating maksimum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rating_from'],
                                fn(Builder $query, $rating): Builder => $query->where('average_rating', '>=', $rating),
                            )
                            ->when(
                                $data['rating_to'],
                                fn(Builder $query, $rating): Builder => $query->where('average_rating', '<=', $rating),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggle_status')
                    ->label(fn($record) => $record->status === 'active' ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => $record->status === 'active'
                                ? 'inactive'
                                : 'active'
                        ]);
                    }),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktifkan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'active'])),

                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'inactive'])),

                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
