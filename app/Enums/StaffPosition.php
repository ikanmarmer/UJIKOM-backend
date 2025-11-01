<?php

namespace App\Enums;

class StaffPosition
{
    const MANAGER = 'manager';
    const RESEPSIONIS = 'resepsionis';

    public static function all(): array
    {
        return [
            self::MANAGER,
            self::RESEPSIONIS,
        ];
    }

    public static function labels(): array
    {
        return [
            self::MANAGER => 'Manager',
            self::RESEPSIONIS => 'Resepsionis',
        ];
    }
}