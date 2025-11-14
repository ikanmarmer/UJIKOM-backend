<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Schemas;

use App\Models\Hotel;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Hotel & Kategori Kamar')
                        ->schema([
                            Section::make('Pilih Hotel & Kategori Kamar')
                                ->schema([
                                    Select::make('hotel_id')
                                        ->label('Hotel')
                                        ->options(function () {
                                            return Hotel::whereHas('staff', function ($query) {
                                                $query->where('user_id', auth()->id())
                                                    ->where('is_active', true);
                                            })
                                                ->where('status', 'active')
                                                ->pluck('name', 'id');
                                        })
                                        ->required()
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('room_category_id', null))
                                        ->disabled(fn(string $context) => $context === 'edit'),

                                    Select::make('room_category_id')
                                        ->label('Kategori Kamar')
                                        ->options(function (Get $get) {
                                            $hotelId = $get('hotel_id');
                                            if (!$hotelId) {
                                                return [];
                                            }

                                            return RoomCategory::where('hotel_id', $hotelId)
                                                ->get()
                                                ->mapWithKeys(function ($category) {
                                                    return [
                                                        $category->id => "{$category->name} (Maks {$category->max_guests} tamu/kamar)"
                                                    ];
                                                });
                                        })
                                        ->required()
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                            if ($state) {
                                                $category = RoomCategory::find($state);
                                                $set('price_per_night', $category?->price_per_night ?? 0);
                                                $set('max_guests_allowed', $category?->max_guests ?? 2);

                                                // Calculate total price if dates exist
                                                $checkIn = $get('check_in_date');
                                                $checkOut = $get('check_out_date');
                                                $rooms = $get('number_of_rooms') ?? 1;

                                                if ($checkIn && $checkOut) {
                                                    $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
                                                    $set('nights', $nights);
                                                    $set('total_price', $category->price_per_night * $nights * $rooms);
                                                }
                                            }
                                        })
                                        ->disabled(fn(string $context) => $context === 'edit'),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('price_per_night')
                                                ->label('Harga per Malam')
                                                ->prefix('IDR')
                                                ->disabled()
                                                ->dehydrated()
                                                ->default(0)
                                                ->numeric(),

                                            Hidden::make('max_guests_allowed')
                                                ->default(2),
                                        ]),
                                ]),
                        ]),

                    Step::make('Tanggal & Kamar')
                        ->schema([
                            Section::make('Informasi Pemesanan')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('check_in_date')
                                                ->label('Tanggal Check-in')
                                                ->required()
                                                ->native(false)
                                                ->minDate(now())
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                    $checkOut = $get('check_out_date');
                                                    if ($state && $checkOut) {
                                                        $nights = Carbon::parse($state)->diffInDays(Carbon::parse($checkOut));
                                                        $set('nights', max(1, $nights));

                                                        $pricePerNight = $get('price_per_night');
                                                        $rooms = $get('number_of_rooms') ?? 1;
                                                        $set('total_price', $pricePerNight * $nights * $rooms);
                                                    }
                                                })
                                                ->disabled(fn(string $context) => $context === 'edit'),

                                            DatePicker::make('check_out_date')
                                                ->label('Tanggal Check-out')
                                                ->required()
                                                ->native(false)
                                                ->minDate(fn(Get $get) => $get('check_in_date') ? Carbon::parse($get('check_in_date'))->addDay() : now()->addDay())
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                    $checkIn = $get('check_in_date');
                                                    if ($checkIn && $state) {
                                                        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($state));
                                                        $set('nights', max(1, $nights));

                                                        $pricePerNight = $get('price_per_night');
                                                        $rooms = $get('number_of_rooms') ?? 1;
                                                        $set('total_price', $pricePerNight * $nights * $rooms);
                                                    }
                                                })
                                                ->disabled(fn(string $context) => $context === 'edit'),

                                            TextInput::make('nights')
                                                ->label('Jumlah Malam')
                                                ->disabled()
                                                ->dehydrated()
                                                ->default(1)
                                                ->suffix('malam'),

                                            TextInput::make('number_of_rooms')
                                                ->label('Jumlah Kamar')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(6)
                                                ->default(1)
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                    $pricePerNight = $get('price_per_night');
                                                    $nights = $get('nights') ?? 1;
                                                    $set('total_price', $pricePerNight * $nights * $state);

                                                    // Reset room details when number changes
                                                    $roomDetails = $get('room_details') ?? [];
                                                    $maxGuests = $get('max_guests_allowed') ?? 2;

                                                    $newRoomDetails = [];
                                                    for ($i = 0; $i < $state; $i++) {
                                                        $newRoomDetails[] = $roomDetails[$i] ?? [
                                                            'room_label' => 'Kamar ' . ($i + 1),
                                                            'guests_count' => 1,
                                                        ];
                                                    }
                                                    $set('room_details', $newRoomDetails);

                                                    // Calculate total guests
                                                    $totalGuests = array_sum(array_column($newRoomDetails, 'guests_count'));
                                                    $set('total_guests', $totalGuests);
                                                })
                                                ->disabled(fn(string $context) => $context === 'edit'),
                                        ]),
                                ]),

                            Section::make('Detail Tamu per Kamar')
                                ->description('Tentukan jumlah tamu untuk setiap kamar')
                                ->schema([
                                    Repeater::make('room_details')
                                        ->label('')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('room_label')
                                                        ->label('Kamar')
                                                        ->disabled()
                                                        ->dehydrated(),

                                                    TextInput::make('guests_count')
                                                        ->label('Jumlah Tamu')
                                                        ->required()
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->maxValue(fn(Get $get) => $get('../../max_guests_allowed') ?? 2)
                                                        ->default(1)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                            $roomDetails = $get('../../room_details') ?? [];
                                                            $guestsCounts = array_column($roomDetails, 'guests_count');
                                                            $totalGuests = array_sum(array_map('intval', $guestsCounts));
                                                            $set('../../total_guests', $totalGuests);
                                                        })
                                                        ->helperText(fn(Get $get) => "Maksimal " . ($get('../../max_guests_allowed') ?? 2) . " tamu per kamar"),
                                                ]),
                                        ])
                                        ->defaultItems(1)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->columnSpanFull(),

                                    TextInput::make('total_guests')
                                        ->label('Total Tamu')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(1)
                                        ->suffix('tamu')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Total Harga')
                                ->schema([
                                    TextInput::make('total_price')
                                        ->label('Total Pembayaran')
                                        ->prefix('IDR')
                                        ->disabled()
                                        ->dehydrated()
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    Step::make('Data Tamu')
                        ->schema([
                            Section::make('Informasi Tamu')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('guest_name')
                                                ->label('Nama Tamu')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('guest_email')
                                                ->label('Email Tamu (Opsional)')
                                                ->email()
                                                ->maxLength(255)
                                                ->placeholder('contoh@email.com')
                                                ->helperText('Kosongkan jika tamu tidak memiliki email'),

                                            Textarea::make('special_requests')
                                                ->label('Permintaan Khusus')
                                                ->rows(3)
                                                ->maxLength(1000)
                                                ->placeholder('Permintaan atau catatan khusus...')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),

                    Step::make('Konfirmasi')
                        ->schema([
                            Section::make('Status Booking')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status Booking')
                                        ->options([
                                            'confirmed' => 'Confirmed (Lunas)',
                                            'pending' => 'Pending (Belum Lunas)',
                                        ])
                                        ->default('confirmed')
                                        ->required()
                                        ->native(false)
                                        ->helperText('Pilih "Confirmed" jika tamu sudah membayar'),

                                    Select::make('payment_status')
                                        ->label('Status Pembayaran')
                                        ->options([
                                            'paid' => 'Lunas',
                                            'unpaid' => 'Belum Dibayar',
                                        ])
                                        ->default('paid')
                                        ->required()
                                        ->native(false),

                                    Hidden::make('user_id')
                                        ->default(null),

                                    Hidden::make('payment_method')
                                        ->default('offline'),
                                ]),

                            Section::make('Ringkasan Pemesanan')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('summary_hotel')
                                                ->label('Hotel')
                                                ->formatStateUsing(function (Get $get) {
                                                    $hotelId = $get('hotel_id');
                                                    if (!$hotelId)
                                                        return '-';
                                                    $hotel = Hotel::find($hotelId);
                                                    return $hotel ? $hotel->name : '-';
                                                })
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('summary_category')
                                                ->label('Kategori Kamar')
                                                ->formatStateUsing(function (Get $get) {
                                                    $categoryId = $get('room_category_id');
                                                    if (!$categoryId)
                                                        return '-';
                                                    $category = RoomCategory::find($categoryId);
                                                    return $category ? $category->name : '-';
                                                })
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('summary_dates')
                                                ->label('Periode')
                                                ->formatStateUsing(function (Get $get) {
                                                    $checkIn = $get('check_in_date');
                                                    $checkOut = $get('check_out_date');
                                                    if (!$checkIn || !$checkOut)
                                                        return '-';

                                                    return Carbon::parse($checkIn)->format('d M Y') . ' - ' .
                                                        Carbon::parse($checkOut)->format('d M Y');
                                                })
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('summary_rooms')
                                                ->label('Kamar & Tamu')
                                                ->formatStateUsing(function (Get $get) {
                                                    $rooms = $get('number_of_rooms') ?? 0;
                                                    $guests = $get('total_guests') ?? 0;
                                                    return "{$rooms} kamar, {$guests} tamu";
                                                })
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('summary_room_details')
                                                ->label('Detail per Kamar')
                                                ->formatStateUsing(function (Get $get) {
                                                    $roomDetails = $get('room_details') ?? [];
                                                    if (empty($roomDetails))
                                                        return '-';

                                                    return collect($roomDetails)
                                                        ->map(fn($room) => "{$room['room_label']}: {$room['guests_count']} tamu")
                                                        ->join(', ');
                                                })
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpanFull(),

                                            TextInput::make('summary_total')
                                                ->label('Total Pembayaran')
                                                ->prefix('IDR')
                                                ->formatStateUsing(fn(Get $get) => number_format($get('total_price') ?? 0, 0, ',', '.'))
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(fn(string $context) => $context === 'edit')
                    ->persistStepInQueryString(),
            ]);
    }
}
