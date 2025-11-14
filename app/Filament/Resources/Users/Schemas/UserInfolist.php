<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Carbon\Carbon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->schema([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                'lg' => 3,
                            ])
                            ->schema([
                                // LEFT COLUMN - Avatar & Basic Info (Compact)
                                Group::make()
                                    ->schema([
                                        Section::make()
                                            ->schema([
                                                Grid::make()
                                                    ->columns(1)
                                                    ->schema([
                                                        ImageEntry::make('avatar')
                                                            ->label('')
                                                            ->disk('public')
                                                            ->circular()
                                                            ->grow(false)
                                                            ->extraAttributes([
                                                                'class' => 'w-32 h-32 mx-auto',
                                                            ])
                                                            ->extraImgAttributes([
                                                                'class' => 'w-full h-full object-cover',
                                                                'alt' => 'Avatar Pengguna',
                                                            ]),

                                                        TextEntry::make('name')
                                                            ->label('Nama Lengkap')
                                                            ->formatStateUsing(fn($state) => $state ?: '-')
                                                            ->extraAttributes(['class' => 'text-lg font-semibold text-center'])
                                                            ->columnSpanFull(),

                                                        TextEntry::make('role')
                                                            ->label('Peran')
                                                            ->badge()
                                                            ->formatStateUsing(fn($state) => self::getRoleLabel($state))
                                                            ->color(fn($state) => self::getRoleColor($state))
                                                            ->grow(false)
                                                            ->extraAttributes(['class' => 'mx-auto']),
                                                    ])
                                                    ->columns(1),
                                            ])
                                            ->compact(),
                                    ])
                                    ->columnSpan(['default' => 1, 'lg' => 1]),

                                // CENTER COLUMN - Contact Information
                                Group::make()
                                    ->schema([
                                        Section::make('Informasi Kontak')
                                            ->schema([
                                                TextEntry::make('email')
                                                    ->label('Email')
                                                    ->icon('heroicon-o-envelope')
                                                    ->formatStateUsing(fn($state) => $state ?: '-')
                                                    ->columnSpanFull(),

                                                TextEntry::make('phone')
                                                    ->label('No. Telepon')
                                                    ->placeholder('Belum diatur')
                                                    ->icon('heroicon-o-phone')
                                                    ->formatStateUsing(fn($state) => self::formatPhoneNumber($state))
                                                    ->columnSpanFull(),
                                            ])
                                            ->compact(),
                                    ])
                                    ->columnSpan(['default' => 1, 'lg' => 1]),

                                // RIGHT COLUMN - Technical Information
                                Group::make()
                                    ->schema([
                                        Section::make('Informasi Sistem')
                                            ->schema([
                                                Grid::make()
                                                    ->columns(1)
                                                    ->schema([
                                                        TextEntry::make('created_at')
                                                            ->label('Dibuat')
                                                            ->icon('heroicon-o-calendar')
                                                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('d F Y, H:i') : '-'),

                                                        TextEntry::make('updated_at')
                                                            ->label('Diperbarui')
                                                            ->icon('heroicon-o-clock')
                                                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('d F Y, H:i') : '-'),

                                                        IconEntry::make('google_id')
                                                            ->label('Terhubung Google')
                                                            ->boolean()
                                                            ->size(IconSize::Medium)
                                                            ->grow(false),
                                                    ]),
                                            ])
                                            ->compact(),
                                    ])
                                    ->columnSpan(['default' => 1, 'lg' => 1]),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Format phone number to Indonesian format
     */
    private static function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '-';
        }

        $cleaned = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($cleaned, '62')) {
            $formatted = '+' . $cleaned;
        } elseif (str_starts_with($cleaned, '0')) {
            $formatted = '+62' . substr($cleaned, 1);
        } else {
            $formatted = '+' . $cleaned;
        }

        return preg_replace('/(\+\d{2})(\d{3})(\d{3,4})(\d{3,4})/', '$1 $2 $3 $4', $formatted);
    }

    /**
     * Get human-readable role label
     */
    private static function getRoleLabel($state): string
    {
        try {
            if ($state instanceof Role) {
                return $state->label();
            }

            if (is_string($state) && in_array($state, Role::all(), true)) {
                return Role::from($state)->label();
            }
        } catch (\Throwable $e) {
            // fall through to default
        }

        return is_string($state) && $state !== '' ? ucfirst($state) : 'User';
    }

    /**
     * Get color for role badge
     */
    private static function getRoleColor($state): string
    {
        $role = null;

        if ($state instanceof Role) {
            $role = $state;
        } elseif (is_string($state) && in_array($state, Role::all(), true)) {
            try {
                $role = Role::from($state);
            } catch (\Throwable $e) {
                $role = null;
            }
        }

        return match ($role?->value) {
            Role::OWNER->value => 'success',
            Role::RESEPSIONIS->value => 'primary',
            Role::USER->value => 'secondary',
            default => 'primary',
        };
    }
}
