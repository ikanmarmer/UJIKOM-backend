<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('role', '!=', Role::ADMIN->value))
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('storage/avatars/image.png')),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->placeholder('Belum diatur')
                    ->limit(5)
                    ->tooltip(fn($record) => $record->phone),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn($state): string => match ($state instanceof Role ? $state->value : (string) $state) {
                        Role::OWNER->value => 'primary',
                        Role::RESEPSIONIS->value => 'info',
                        Role::USER->value => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state instanceof Role ? $state->label() : Role::from((string) $state)->label())
                    ->sortable(),

                IconColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('google_id')
                    ->label('Google')
                    ->boolean()
                    ->sortable()
                    ->getStateUsing(fn($record) => !is_null($record->google_id))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Pemesanan')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        Role::USER->value => Role::USER->label(),
                        Role::OWNER->value => Role::OWNER->label(),
                        Role::RESEPSIONIS->value => Role::RESEPSIONIS->label(),
                    ])
                    ->native(false),

                TernaryFilter::make('is_verified')
                    ->label('Email Terverifikasi')
                    ->placeholder('Semua pengguna')
                    ->trueLabel('Hanya terverifikasi')
                    ->falseLabel('Hanya belum terverifikasi'),

                TernaryFilter::make('google_id')
                    ->label('Akun Google')
                    ->placeholder('Semua pengguna')
                    ->trueLabel('Pengguna Google')
                    ->falseLabel('Pengguna Email')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('google_id'),
                        false: fn(Builder $query) => $query->whereNull('google_id'),
                    ),

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
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->selectCurrentPageOnly();
    }
}
