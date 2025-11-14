<?php

namespace App\Filament\Owner\Resources\HotelStaff\Pages;

use App\Filament\Owner\Resources\HotelStaff\HotelStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotelStaff extends ListRecords
{
    protected static string $resource = HotelStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Staff')
                ->icon('heroicon-o-plus'),
        ];
    }
}
