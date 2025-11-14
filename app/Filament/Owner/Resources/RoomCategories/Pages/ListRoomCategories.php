<?php

namespace App\Filament\Owner\Resources\RoomCategories\Pages;

use App\Filament\Owner\Resources\RoomCategories\RoomCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoomCategories extends ListRecords
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Kategori Kamar')
                ->icon('heroicon-o-plus'),
        ];
    }
}
