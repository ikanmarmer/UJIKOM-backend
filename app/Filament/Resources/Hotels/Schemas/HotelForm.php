<?php

namespace App\Filament\Resources\Hotels\Schemas;

use App\Models\City;
use App\Models\User;
use App\Enums\Role;
use App\Enums\HotelStatus;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Informasi Pemilik')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('owner_id')
                                        ->label('Pilih Pemilik yang Ada')
                                        ->options(fn() => User::where('role', Role::OWNER->value)->pluck('name', 'id'))
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set): void {
                                            if ($state) {
                                                $set('create_new_owner', false);
                                            }
                                        })
                                        ->helperText('Pilih pemilik hotel yang sudah ada atau buat yang baru di bawah'),

                                    Toggle::make('create_new_owner')
                                        ->label('Buat Akun Pemilik Baru')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state) {
                                                $set('owner_id', null);
                                            }
                                        }),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('owner_name')
                                                ->label('Nama Pemilik')
                                                ->required(fn(Get $get) => $get('create_new_owner'))
                                                ->maxLength(255)
                                                ->dehydrated(fn(Get $get) => $get('create_new_owner')),

                                            TextInput::make('owner_email')
                                                ->label('Email Pemilik')
                                                ->email()
                                                ->required(fn(Get $get) => $get('create_new_owner'))
                                                ->unique(User::class, 'email', ignoreRecord: true)
                                                ->maxLength(255)
                                                ->dehydrated(fn(Get $get) => $get('create_new_owner')),

                                            TextInput::make('owner_phone')
                                                ->label('Telepon Pemilik')
                                                ->maxLength(20)
                                                ->dehydrated(fn(Get $get) => $get('create_new_owner')),

                                            TextInput::make('owner_password')
                                                ->label('Kata Sandi Pemilik')
                                                ->password()
                                                ->required(fn(Get $get) => $get('create_new_owner'))
                                                ->minLength(8)
                                                ->maxLength(255)
                                                ->helperText('Minimal 8 karakter')
                                                ->revealable()
                                                ->dehydrated(fn(Get $get) => $get('create_new_owner')),
                                        ])
                                        ->visible(fn(Get $get) => $get('create_new_owner')),
                                ])
                        ])
                        ->visible(fn(string $context) => $context === 'create'),

                    Step::make('Informasi Hotel')
                        ->schema([
                            Section::make('Informasi Dasar')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nama Hotel')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(
                                            fn(string $state, Set $set) =>
                                            $set('slug', Str::slug($state))
                                        )
                                        ->columnSpanFull(),

                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Versi nama yang ramah URL'),

                                    Select::make('city_id')
                                        ->label('Kota')
                                        ->options(City::orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->native(false)
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->label('Nama Kota')
                                                ->required()
                                                ->maxLength(255)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(
                                                    fn(string $state, Set $set) =>
                                                    $set('slug', Str::slug($state))
                                                ),
                                            TextInput::make('province')
                                                ->label('Provinsi')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('slug')
                                                ->required()
                                                ->maxLength(255)
                                                ->unique(City::class, 'slug'),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return City::create($data)->id;
                                        }),

                                    Select::make('star_rating')
                                        ->label('Rating Bintang')
                                        ->options([
                                            1 => '1 Bintang',
                                            2 => '2 Bintang',
                                            3 => '3 Bintang',
                                            4 => '4 Bintang',
                                            5 => '5 Bintang',
                                        ])
                                        ->default(3)
                                        ->required()
                                        ->native(false),

                                    Select::make('status')
                                        ->options([
                                            HotelStatus::ACTIVE => 'Aktif',
                                            HotelStatus::INACTIVE => 'Tidak Aktif',
                                            HotelStatus::PENDING => 'Menunggu',
                                        ])
                                        ->default(HotelStatus::ACTIVE)
                                        ->required()
                                        ->native(false),
                                ])
                                ->columns(2),

                            Section::make('Informasi Kontak')
                                ->schema([
                                    Textarea::make('address')
                                        ->label('Alamat')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    TextInput::make('phone')
                                        ->label('Telepon')
                                        ->required()
                                        ->maxLength(20),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Deskripsi & Gambar')
                        ->schema([
                            Section::make('Deskripsi')
                                ->schema([
                                    Textarea::make('description')
                                        ->label('Deskripsi')
                                        ->required()
                                        ->rows(4)
                                        ->columnSpanFull()
                                        ->helperText('Deskripsi lengkap tentang hotel, fasilitas, dan keunggulan'),
                                ]),

                            Section::make('Gambar Hotel')
                                ->schema([
                                    FileUpload::make('images')
                                        ->label('Gambar')
                                        ->image()
                                        ->multiple()
                                        ->directory('hotels')
                                        ->disk('public')
                                        ->imageEditor()
                                        ->maxSize(2048)
                                        ->maxFiles(5)
                                        ->reorderable()
                                        ->helperText('Maksimal 5 gambar, 2MB per gambar. Gambar pertama akan menjadi thumbnail.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Fasilitas')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('amenities')
                                        ->label('Fasilitas')
                                        ->relationship('amenities', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->native(false)
                                        ->helperText('Pilih fasilitas hotel (WiFi, Kolam Renang, Gym, dll.)')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(fn(string $context) => $context === 'edit'),
            ]);
    }
}
