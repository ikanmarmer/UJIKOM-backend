<?php

namespace App\Filament\Resources\Hotels\Pages;

use App\Filament\Resources\Hotels\HotelResource;
use App\Models\User;
use App\Enums\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle owner creation if needed
        if (isset($data['create_new_owner']) && $data['create_new_owner']) {
            DB::beginTransaction();
            try {
                // Create new owner account
                $owner = User::create([
                    'name' => $data['owner_name'],
                    'email' => $data['owner_email'],
                    'phone' => $data['owner_phone'] ?? null,
                    'password' => Hash::make($data['owner_password']),
                    'role' => Role::OWNER->value,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);

                $data['owner_id'] = $owner->id;

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        // Clean up temporary owner data fields
        unset(
            $data['create_new_owner'],
            $data['owner_name'],
            $data['owner_email'],
            $data['owner_phone'],
            $data['owner_password']
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Hotel created successfully!';
    }

}
