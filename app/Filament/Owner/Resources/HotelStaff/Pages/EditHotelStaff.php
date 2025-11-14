<?php

namespace App\Filament\Owner\Resources\HotelStaff\Pages;

use App\Filament\Owner\Resources\HotelStaff\HotelStaffResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHotelStaff extends EditRecord
{
    protected static string $resource = HotelStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
