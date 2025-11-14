<?php

namespace App\Filament\Owner\Resources\HotelStaff\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class HotelStaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('user.avatar')
                    ->label('Avatar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('storage/avatars/image.png')),

                TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->user->email),

                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office-2')
                    ->badge()
                    ->color('info'),

                TextColumn::make('user.phone')
                    ->label('Telepon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->placeholder('Tidak ada')
                    ->toggleable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Aktif' : 'Tidak Aktif')
                    ->sortable(),

                TextColumn::make('assigned_at')
                    ->label('Tanggal Ditugaskan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
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

                Filter::make('is_active')
                    ->label('Hanya Aktif')
                    ->query(fn($query) => $query->where('is_active', true))
                    ->default(),

                Filter::make('inactive')
                    ->label('Hanya Tidak Aktif')
                    ->query(fn($query) => $query->where('is_active', false)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggle_status')
                    ->label(fn($record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if ($record->is_active) {
                            $record->terminate();
                        } else {
                            $record->reactivate();
                        }
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
                        ->action(fn($records) => $records->each->reactivate()),

                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->terminate()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
