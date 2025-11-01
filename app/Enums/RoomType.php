<?php

namespace App\Enums;

class RoomType
{
    const SINGLE = 'single';
    const DOUBLE = 'double';
    const TWIN = 'twin';
    const SUITE = 'suite';
    const DELUXE = 'deluxe';
    const STANDARD = 'standard';

    public static function all(): array
    {
        return [
            self::SINGLE,
            self::DOUBLE,
            self::TWIN,
            self::SUITE,
            self::DELUXE,
            self::STANDARD,
        ];
    }

    public static function labels(): array
    {
        return [
            self::SINGLE => 'Single',
            self::DOUBLE => 'Double',
            self::TWIN => 'Twin',
            self::SUITE => 'Suite',
            self::DELUXE => 'Deluxe',
            self::STANDARD => 'Standard',
        ];
    }
}