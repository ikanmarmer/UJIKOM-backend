<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Ulasan')
                    ->schema([
                        ImageEntry::make('user.avatar')
                            ->label('Avatar Pengulas')
                            ->circular()
                            ->disk('public')
                            ->defaultImageUrl(url('storage/avatars/image.png'))
                            ->columnSpanFull(),

                        TextEntry::make('user.name')
                            ->label('Pengulas')
                            ->size('lg')
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('hotel.name')
                            ->label('Hotel')
                            ->icon('heroicon-m-building-office-2')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('booking.booking_code')
                            ->label('Kode Booking')
                            ->icon('heroicon-m-ticket')
                            ->copyable()
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('rating')
                            ->label('Penilaian')
                            ->formatStateUsing(fn($state) => str_repeat('⭐', $state) . " ({$state}/5)")
                            ->size('lg'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->size('lg')
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
                            }),
                    ])
                    ->columns(2),

                Section::make('Isi Ulasan')
                    ->schema([
                        TextEntry::make('comment')
                            ->label('Komentar')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Foto-Foto Ulasan')
                    ->schema([
                        ImageEntry::make('images')
                            ->label('Foto')
                            ->disk('public')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => !empty($record->images))
                    ->collapsible(),

                Section::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dikirim Pada')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-calendar'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diubah')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-clock'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
