<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('storage/images/image-placeholder.png')),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->province),

                TextColumn::make('province')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-map')
                    ->toggleable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-link')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hotels_count')
                    ->counts('hotels')
                    ->label('Hotel')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-building-office-2'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_hotels')
                    ->label('Memiliki Hotel')
                    ->query(fn($query) => $query->has('hotels')),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Dari'),
                        DatePicker::make('created_until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->hidden(fn($record) => $record->hotels()->count() > 0),
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
