<?php

namespace App\Enums;

class HotelStatus
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const PENDING = 'pending';

    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::PENDING,
        ];
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::PENDING => 'Pending',
        ];
    }
}