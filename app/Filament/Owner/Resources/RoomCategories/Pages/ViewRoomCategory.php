<?php

namespace App\Filament\Owner\Resources\RoomCategories\Pages;

use App\Filament\Owner\Resources\RoomCategories\RoomCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRoomCategory extends ViewRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
        ];
    }
}
