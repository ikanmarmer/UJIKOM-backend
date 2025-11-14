<?php

namespace App\Filament\Owner\Resources\Bookings\Pages;

use App\Filament\Owner\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
