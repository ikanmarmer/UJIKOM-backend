<?php

namespace App\Enums;

class UserRole
{
    const USER = 'user';
    const ADMIN = 'admin';
    const OWNER = 'owner';
    const MANAGER = 'manager';
    const RESEPSIONIS = 'resepsionis';

    public static function all(): array
    {
        return [
            self::USER,
            self::ADMIN,
            self::OWNER,
            self::MANAGER,
            self::RESEPSIONIS,
        ];
    }

    public static function labels(): array
    {
        return [
            self::USER => 'User',
            self::ADMIN => 'Admin',
            self::OWNER => 'Owner',
            self::MANAGER => 'Manager',
            self::RESEPSIONIS => 'Resepsionis',
        ];
    }
}