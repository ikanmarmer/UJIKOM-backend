<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_code')
                    ->required(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('hotel_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('room_category_id')
                    ->required()
                    ->numeric(),
                TextInput::make('guest_name')
                    ->required(),
                TextInput::make('guest_email')
                    ->email()
                    ->required(),
                DatePicker::make('check_in_date')
                    ->required(),
                DatePicker::make('check_out_date')
                    ->required(),
                TextInput::make('nights')
                    ->required()
                    ->numeric(),
                TextInput::make('number_of_rooms')
                    ->required()
                    ->numeric(),
                TextInput::make('guests_per_room')
                    ->required()
                    ->numeric(),
                TextInput::make('total_guests')
                    ->required()
                    ->numeric(),
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric(),
                TextInput::make('total_price')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('unpaid'),
                Textarea::make('special_requests')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
