<?php

namespace App\Filament\Resources\Amenities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AmenitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-sparkles'),

                TextColumn::make('hotels_count')
                    ->counts('hotels')
                    ->label('Digunakan di Hotel')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-building-office-2'),

                TextColumn::make('room_categories_count')
                    ->counts('roomCategories')
                    ->label('Digunakan di Kamar')
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
                Filter::make('used_in_hotels')
                    ->label('Digunakan di Hotel')
                    ->query(fn($query) => $query->has('hotels')),

                Filter::make('used_in_rooms')
                    ->label('Digunakan di Kategori Kamar')
                    ->query(fn($query) => $query->has('roomCategories')),

                Filter::make('unused')
                    ->label('Fasilitas Tidak Digunakan')
                    ->query(fn($query) => $query->doesntHave('hotels')->doesntHave('roomCategories')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->hotels()->count() > 0 || $record->roomCategories()->count() > 0) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Tidak Dapat Dihapus')
                                ->body('Fasilitas ini sedang digunakan di hotel atau kategori kamar.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
