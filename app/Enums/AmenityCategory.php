<?php

namespace App\Enums;

class AmenityCategory
{
    const HOTEL = 'hotel';
    const ROOM = 'room';

    public static function all(): array
    {
        return [
            self::HOTEL,
            self::ROOM,
        ];
    }

    public static function labels(): array
    {
        return [
            self::HOTEL => 'Hotel Amenity',
            self::ROOM => 'Room Amenity',
        ];
    }
}