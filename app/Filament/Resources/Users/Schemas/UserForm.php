<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),

                        Select::make('role')
                            ->label('Peran')
                            ->options([
                                Role::USER->value => Role::USER->label(),
                                Role::OWNER->value => Role::OWNER->label(),
                                Role::RESEPSIONIS->value => Role::RESEPSIONIS->label(),
                                Role::ADMIN->value => Role::ADMIN->label(),
                            ])
                            ->required()
                            ->native(false)
                            ->default(Role::USER->value),

                        Toggle::make('is_verified')
                            ->label('Email Terverifikasi')
                            ->default(true)
                            ->helperText('Tandai email sebagai terverifikasi')
                            ->hiddenOn('create')
                            ->hiddenOn('edit'),
                    ])
                    ->columns(2),

                Section::make('Kata Sandi')
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Biarkan kosong untuk mempertahankan kata sandi saat ini (mode edit)'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Kata Sandi')
                            ->password()
                            ->dehydrated(false)
                            ->maxLength(255)
                            ->same('password')
                            ->requiredWith('password'),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => !$record?->google_id),

                Section::make('Foto Profil')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->disk('public')
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->maxSize(2048)
                            ->helperText('Maksimal 2MB. Disarankan 300x300px.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
