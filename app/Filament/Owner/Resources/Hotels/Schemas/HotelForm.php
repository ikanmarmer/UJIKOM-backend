<?php

namespace App\Filament\Owner\Resources\Hotels\Schemas;

use App\Enums\HotelStatus;
use App\Models\City;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Informasi Dasar')
                        ->schema([
                            Section::make('Detail Hotel')
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
                                        ->helperText('Versi nama yang ramah URL')
                                        ->columnSpan(1),

                                    Select::make('city_id')
                                        ->label('Kota')
                                        ->options(City::orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(1),

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
                                        ->native(false)
                                        ->columnSpan(1),

                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            HotelStatus::ACTIVE => 'Aktif',
                                            HotelStatus::INACTIVE => 'Tidak Aktif',
                                            HotelStatus::PENDING => 'Menunggu',
                                        ])
                                        ->default(HotelStatus::ACTIVE)
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(1),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Informasi Kontak')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Textarea::make('address')
                                        ->label('Alamat Lengkap')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    TextInput::make('phone')
                                        ->label('Nomor Telepon')
                                        ->required()
                                        ->tel()
                                        ->maxLength(20)
                                        ->columnSpan(1),

                                    TextInput::make('email')
                                        ->label('Alamat Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Deskripsi & Gambar')
                        ->schema([
                            Section::make('Deskripsi')
                                ->schema([
                                    Textarea::make('description')
                                        ->label('Deskripsi Hotel')
                                        ->required()
                                        ->rows(4)
                                        ->maxLength(1000)
                                        ->helperText('Deskripsikan hotel, fasilitas, dan keunggulan unik Anda')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Gambar Hotel')
                                ->schema([
                                    FileUpload::make('images')
                                        ->label('Unggah Gambar')
                                        ->image()
                                        ->multiple()
                                        ->directory('hotels')
                                        ->disk('public')
                                        ->imageEditor()
                                        ->maxSize(2048)
                                        ->maxFiles(5)
                                        ->reorderable()
                                        ->helperText('Unggah hingga 5 gambar. Gambar pertama akan menjadi thumbnail.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Fasilitas')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('amenities')
                                        ->label('Fasilitas Hotel')
                                        ->relationship('amenities', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->native(false)
                                        ->helperText('Pilih fasilitas yang tersedia di hotel Anda')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(fn(string $context) => $context === 'edit'),
            ]);
    }
}
