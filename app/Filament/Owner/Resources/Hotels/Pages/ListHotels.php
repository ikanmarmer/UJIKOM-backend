<?php

namespace App\Filament\Owner\Resources\Hotels\Pages;

use App\Filament\Owner\Resources\Hotels\HotelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotels extends ListRecords
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Hotel')
                ->icon('heroicon-o-plus'),
        ];
    }
}
