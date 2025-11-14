<?php

namespace App\Filament\Owner\Resources\Hotels\Pages;

use App\Filament\Owner\Resources\Hotels\HotelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
