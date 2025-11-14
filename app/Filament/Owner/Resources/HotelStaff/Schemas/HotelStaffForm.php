<?php

namespace App\Filament\Owner\Resources\HotelStaff\Schemas;

use App\Enums\Role;
use App\Models\Hotel;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Hash;

class HotelStaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penugasan Staf')
                    ->schema([
                        Select::make('hotel_id')
                            ->label('Hotel')
                            ->options(function () {
                                return Hotel::where('owner_id', auth()->id())
                                    ->where('status', 'active')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->disabled(fn(string $context) => $context === 'edit')
                            ->dehydrated()
                            ->columnSpanFull(),

                        Select::make('user_id')
                            ->label('Pilih Resepsionis yang Ada')
                            ->options(function () {
                                return User::where('role', Role::RESEPSIONIS->value)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->helperText('Pilih resepsionis yang ada atau buat yang baru di bawah')
                            ->disabled(fn(string $context) => $context === 'edit')
                            ->dehydrated(),

                        Toggle::make('create_new_user')
                            ->label('Buat Akun Resepsionis Baru')
                            ->live()
                            ->visible(fn(string $context) => $context === 'create'),
                    ]),

                Section::make('Informasi Resepsionis Baru')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('new_user_name')
                                    ->label('Nama Lengkap')
                                    ->required(fn(Get $get) => $get('create_new_user'))
                                    ->maxLength(255),

                                TextInput::make('new_user_email')
                                    ->label('Email')
                                    ->email()
                                    ->required(fn(Get $get) => $get('create_new_user'))
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('new_user_phone')
                                    ->label('Telepon')
                                    ->maxLength(20),

                                TextInput::make('new_user_password')
                                    ->label('Kata Sandi')
                                    ->password()
                                    ->required(fn(Get $get) => $get('create_new_user'))
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->helperText('Minimal 8 karakter')
                                    ->revealable(),
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('create_new_user'))
                    ->collapsed(false),

                Section::make('Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->helperText('Aktifkan/nonaktifkan staf ini'),

                                DateTimePicker::make('assigned_at')
                                    ->label('Tanggal Ditugaskan')
                                    ->default(now())
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
