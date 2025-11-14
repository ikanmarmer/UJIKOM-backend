<?php

namespace App\Filament\Owner\Resources\RoomCategories\Schemas;

use App\Enums\RoomType;
use App\Models\Hotel;
use App\Models\Room;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class RoomCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Informasi Dasar')
                        ->schema([
                            Section::make('Detail Kategori')
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

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Nama Kategori')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('contoh: Kamar Deluxe Double')
                                                ->helperText('Nama kategori kamar'),

                                            Select::make('type')
                                                ->label('Tipe Kamar')
                                                ->options(RoomType::labels())
                                                ->required()
                                                ->native(false)
                                                ->helperText('Tipe konfigurasi tempat tidur'),

                                            TextInput::make('max_guests')
                                                ->label('Maksimum Tamu')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(10)
                                                ->default(2)
                                                ->suffix('tamu'),

                                            TextInput::make('size')
                                                ->label('Ukuran Kamar (m²)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->suffix('m²')
                                                ->placeholder('contoh: 25.5'),

                                            TextInput::make('price_per_night')
                                                ->label('Harga per Malam')
                                                ->required()
                                                ->numeric()
                                                ->prefix('IDR')
                                                ->minValue(0)
                                                ->step(1000)
                                                ->placeholder('contoh: 500000')
                                                ->extraAttributes(['name' => 'price_per_night']), 
                                        ]),

                                    Textarea::make('description')
                                        ->label('Deskripsi')
                                        ->required()
                                        ->rows(4)
                                        ->maxLength(1000)
                                        ->placeholder('Deskripsikan kategori kamar, fitur, dan fasilitas...')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Gambar & Fasilitas')
                        ->schema([
                            Section::make('Gambar Kamar')
                                ->schema([
                                    FileUpload::make('images')
                                        ->label('Gambar Kategori')
                                        ->image()
                                        ->multiple()
                                        ->directory('room-categories')
                                        ->disk('public')
                                        ->imageEditor()
                                        ->maxSize(2048)
                                        ->maxFiles(5)
                                        ->reorderable()
                                        ->helperText('Unggah hingga 5 gambar. Gambar pertama akan menjadi thumbnail.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Fasilitas Kamar')
                                ->schema([
                                    Select::make('amenities')
                                        ->label('Fasilitas')
                                        ->relationship(
                                            'amenities',
                                            'name',
                                            fn($query, $get) =>
                                            $query->whereHas(
                                                'hotels',
                                                fn($q) =>
                                                $q->where('hotels.id', $get('hotel_id'))
                                                    ->where('hotels.owner_id', auth()->id())
                                            )
                                        )
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->native(false)
                                        ->helperText('Pilih fasilitas yang tersedia di kategori kamar ini')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Manajemen Kamar')
                        ->schema([
                            Section::make('Tambah Kamar')
                                ->schema([
                                    Toggle::make('auto_generate_rooms')
                                        ->label('Generate Kamar Otomatis')
                                        ->helperText('Generate beberapa kamar secara otomatis dengan nomor berurutan')
                                        ->live()
                                        ->default(false)
                                        ->columnSpanFull(),

                                    // Auto Generate Section
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('number_of_rooms')
                                                ->label('Jumlah Kamar yang Akan Dibuat')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(50)
                                                ->default(1)
                                                ->required(fn(Get $get) => $get('auto_generate_rooms'))
                                                ->helperText('Berapa banyak kamar yang akan dibuat'),

                                            TextInput::make('room_number_prefix')
                                                ->label('Prefix Nomor Kamar')
                                                ->maxLength(10)
                                                ->default('R')
                                                ->helperText('Prefix untuk nomor kamar (contoh: R, A, 1)'),

                                            TextInput::make('room_number_start')
                                                ->label('Nomor Kamar Awal')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(101)
                                                ->required(fn(Get $get) => $get('auto_generate_rooms'))
                                                ->helperText('Nomor kamar pertama'),

                                            Select::make('default_floor')
                                                ->label('Lantai Default')
                                                ->options([
                                                    'Ground' => 'Lantai Dasar',
                                                    '1' => 'Lantai 1',
                                                    '2' => 'Lantai 2',
                                                    '3' => 'Lantai 3',
                                                    '4' => 'Lantai 4',
                                                    '5' => 'Lantai 5',
                                                    '6' => 'Lantai 6',
                                                    '7' => 'Lantai 7',
                                                    '8' => 'Lantai 8',
                                                    '9' => 'Lantai 9',
                                                    '10' => 'Lantai 10',
                                                ])
                                                ->default('1')
                                                ->required(fn(Get $get) => $get('auto_generate_rooms'))
                                                ->native(false),
                                        ])
                                        ->visible(fn(Get $get) => $get('auto_generate_rooms')),

                                    // Manual Add Section
                                    Repeater::make('rooms')
                                        ->label('Tambah Kamar Manual')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('room_number')
                                                        ->label('Nomor Kamar')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->placeholder('contoh: 101, A1')
                                                        ->unique(Room::class, 'room_number', ignoreRecord: true),

                                                    Select::make('floor')
                                                        ->label('Lantai')
                                                        ->options([
                                                            'Ground' => 'Lantai Dasar',
                                                            '1' => 'Lantai 1',
                                                            '2' => 'Lantai 2',
                                                            '3' => 'Lantai 3',
                                                            '4' => 'Lantai 4',
                                                            '5' => 'Lantai 5',
                                                            '6' => 'Lantai 6',
                                                            '7' => 'Lantai 7',
                                                            '8' => 'Lantai 8',
                                                            '9' => 'Lantai 9',
                                                            '10' => 'Lantai 10',
                                                        ])
                                                        ->required()
                                                        ->default('1')
                                                        ->native(false),

                                                    Select::make('status')
                                                        ->label('Status')
                                                        ->options([
                                                            'available' => 'Tersedia',
                                                            'occupied' => 'Terisi',
                                                            'maintenance' => 'Perawatan',
                                                            'cleaning' => 'Pembersihan',
                                                        ])
                                                        ->default('available')
                                                        ->required()
                                                        ->native(false),
                                                ]),
                                        ])
                                        ->addActionLabel('Tambah Kamar')
                                        ->reorderable(false)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['room_number'] ?? null)
                                        ->visible(fn(Get $get) => !$get('auto_generate_rooms'))
                                        ->columnSpanFull()
                                        ->minItems(0),
                                ])
                                ->description('Tambahkan kamar fisik ke kategori ini. Anda bisa generate otomatis atau tambah manual.')
                                ->visible(fn(string $context) => $context === 'create'),

                            // Edit Mode: Manage Existing Rooms
                            Section::make('Kelola Kamar yang Ada')
                                ->schema([
                                    Repeater::make('existing_rooms')
                                        ->label('Kamar Saat Ini')
                                        ->relationship('rooms')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('room_number')
                                                        ->label('Nomor Kamar')
                                                        ->required()
                                                        ->disabled()
                                                        ->dehydrated(),

                                                    Select::make('floor')
                                                        ->label('Lantai')
                                                        ->options([
                                                            'Ground' => 'Lantai Dasar',
                                                            '1' => 'Lantai 1',
                                                            '2' => 'Lantai 2',
                                                            '3' => 'Lantai 3',
                                                            '4' => 'Lantai 4',
                                                            '5' => 'Lantai 5',
                                                            '6' => 'Lantai 6',
                                                            '7' => 'Lantai 7',
                                                            '8' => 'Lantai 8',
                                                            '9' => 'Lantai 9',
                                                            '10' => 'Lantai 10',
                                                        ])
                                                        ->required()
                                                        ->native(false),

                                                    Select::make('status')
                                                        ->label('Status')
                                                        ->options([
                                                            'available' => 'Tersedia',
                                                            'occupied' => 'Terisi',
                                                            'maintenance' => 'Perawatan',
                                                            'cleaning' => 'Pembersihan',
                                                        ])
                                                        ->required()
                                                        ->native(false),
                                                ]),
                                        ])
                                        ->deletable()
                                        ->reorderable(false)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['room_number'] ?? null)
                                        ->columnSpanFull(),

                                    // Add More Rooms in Edit Mode
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('add_rooms_count')
                                                ->label('Tambah Kamar Lagi')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(50)
                                                ->helperText('Jumlah kamar tambahan yang akan dibuat')
                                                ->default(0),

                                            TextInput::make('new_room_start_number')
                                                ->label('Nomor Awal')
                                                ->numeric()
                                                ->helperText('Nomor awal untuk kamar baru')
                                                ->default(function (Get $get) {
                                                    // Auto-calculate next room number
                                                    return 101;
                                                }),
                                        ]),
                                ])
                                ->description('Kelola kamar yang ada atau tambahkan lebih banyak kamar ke kategori ini.')
                                ->visible(fn(string $context) => $context === 'edit'),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(fn(string $context) => $context === 'edit'),
            ]);
    }
}
