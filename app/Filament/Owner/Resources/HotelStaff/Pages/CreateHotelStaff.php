<?php

namespace App\Filament\Owner\Resources\HotelStaff\Pages;

use App\Enums\Role;
use App\Models\User;
use App\Filament\Owner\Resources\HotelStaff\HotelStaffResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateHotelStaff extends CreateRecord
{
    protected static string $resource = HotelStaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If creating new user
        if (isset($data['create_new_user']) && $data['create_new_user']) {
            $user = User::create([
                'name' => $data['new_user_name'],
                'email' => $data['new_user_email'],
                'phone' => $data['new_user_phone'] ?? null,
                'password' => Hash::make($data['new_user_password']),
                'role' => Role::RESEPSIONIS->value,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);

            $data['user_id'] = $user->id;
        }

        // Clean up temporary fields
        unset($data['create_new_user']);
        unset($data['new_user_name']);
        unset($data['new_user_email']);
        unset($data['new_user_phone']);
        unset($data['new_user_password']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
