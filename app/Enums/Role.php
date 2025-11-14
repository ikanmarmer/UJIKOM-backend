<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case OWNER = 'owner';
    case RESEPSIONIS = 'resepsionis';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OWNER => 'Owner',
            self::RESEPSIONIS => 'Resepsionis',
            self::USER => 'User',
        };
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
