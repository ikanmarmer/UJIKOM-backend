<?php

namespace App\Filament\Owner\Resources\HotelStaff\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class HotelStaffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Staf')
                    ->schema([
                        ImageEntry::make('user.avatar')
                            ->label('Avatar')
                            ->circular()
                            ->disk('public')
                            ->defaultImageUrl(url('storage/avatars/image.png'))
                            ->columnSpanFull(),

                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Nama')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn($state) => $state ? 'Aktif' : 'Tidak Aktif'),

                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('user.phone')
                                    ->label('Telepon')
                                    ->icon('heroicon-m-phone')
                                    ->default('Tidak tersedia')
                                    ->copyable(),

                                TextEntry::make('hotel.name')
                                    ->label('Hotel yang Ditugaskan')
                                    ->icon('heroicon-m-building-office-2')
                                    ->badge()
                                    ->color('info'),

                                // ===== Perbaikan di sini: konversi enum ke string sebelum ucfirst =====
                                TextEntry::make('user.role')
                                    ->label('Peran')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(function ($state) {
                                        // Jika state adalah BackedEnum (PHP enum dengan nilai), pakai ->value
                                        if ($state instanceof \BackedEnum) {
                                            $value = $state->value;
                                        }
                                        // Jika state adalah UnitEnum (PHP enum tanpa nilai), pakai ->name
                                        elseif ($state instanceof \UnitEnum) {
                                            $value = $state->name;
                                        }
                                        // Jika objek lain (mis. model relation object), coba ubah ke string
                                        elseif (is_object($state)) {
                                            $value = (string) $state;
                                        }
                                        // Jika string atau null
                                        else {
                                            $value = $state;
                                        }

                                        // Pastikan string, lalu format
                                        $value = (string) ($value ?? '');

                                        return $value === '' ? '-' : ucfirst($value);
                                    }),
                            ]),
                    ]),

                Section::make('Detail Penugasan')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('assigned_at')
                                    ->label('Tanggal Ditugaskan')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('terminated_at')
                                    ->label('Tanggal Dihentikan')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar')
                                    ->default('Masih Aktif')
                                    ->visible(fn($record) => !$record->is_active),
                            ]),
                    ]),

                Section::make('Informasi Tambahan')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-clock'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
