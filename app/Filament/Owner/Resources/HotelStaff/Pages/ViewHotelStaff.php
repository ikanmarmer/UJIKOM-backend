<?php

namespace App\Filament\Owner\Resources\HotelStaff\Pages;

use App\Filament\Owner\Resources\HotelStaff\HotelStaffResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHotelStaff extends ViewRecord
{
    protected static string $resource = HotelStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
        ];
    }
}
