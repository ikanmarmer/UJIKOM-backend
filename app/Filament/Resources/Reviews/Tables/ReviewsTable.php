<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

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
                    ->defaultImageUrl(url('storage/images/image-placeholder.png'))
                    ->stacked()
                    ->limit(5)
                    ->limitedRemainingText()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(ReviewStatus $state): string => match ($state) {
                        ReviewStatus::PENDING => 'warning',
                        ReviewStatus::APPROVED => 'success',
                        ReviewStatus::REJECTED => 'danger',
                    })
                    ->formatStateUsing(fn(ReviewStatus $state) => $state->label())
                    ->icon(fn(ReviewStatus $state): string => match ($state) {
                        ReviewStatus::PENDING => 'heroicon-o-clock',
                        ReviewStatus::APPROVED => 'heroicon-o-check-circle',
                        ReviewStatus::REJECTED => 'heroicon-o-x-circle',
                    })
                    ->sortable(),

                TextColumn::make('booking.booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-ticket')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ReviewStatus::PENDING->value => ReviewStatus::PENDING->label(),
                        ReviewStatus::APPROVED->value => ReviewStatus::APPROVED->label(),
                        ReviewStatus::REJECTED->value => ReviewStatus::REJECTED->label(),
                    ])
                    ->native(false),

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
                    ->label('Memiliki Foto')
                    ->query(fn($query) => $query->whereNotNull('images')->where('images', '!=', '[]')),

                Filter::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
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
                ViewAction::make()->label('Lihat'),

                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === ReviewStatus::PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => ReviewStatus::APPROVED]);
                        $record->hotel->updateRating();

                        Notification::make()
                            ->success()
                            ->title('Ulasan Disetujui')
                            ->body('Rating hotel telah diperbarui.')
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === ReviewStatus::PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => ReviewStatus::REJECTED]);

                        Notification::make()
                            ->warning()
                            ->title('Ulasan Ditolak')
                            ->send();
                    }),

                EditAction::make()
                    ->label('Ubah')
                    ->visible(fn($record) => $record->status === ReviewStatus::PENDING),

                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->after(function ($record) {
                        if ($record->status === ReviewStatus::APPROVED) {
                            $record->hotel->updateRating();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => ReviewStatus::APPROVED]);
                                $record->hotel->updateRating();
                            });
                        }),

                    BulkAction::make('reject')
                        ->label('Tolak Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => ReviewStatus::REJECTED])),

                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->after(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === ReviewStatus::APPROVED) {
                                    $record->hotel->updateRating();
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
