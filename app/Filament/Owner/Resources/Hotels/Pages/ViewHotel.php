<?php

namespace App\Filament\Owner\Resources\Hotels\Pages;

use App\Filament\Owner\Resources\Hotels\HotelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
