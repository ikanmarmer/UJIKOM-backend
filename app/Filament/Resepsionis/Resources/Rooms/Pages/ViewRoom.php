<?php

namespace App\Filament\Resepsionis\Resources\Rooms\Pages;

use App\Filament\Resepsionis\Resources\Rooms\RoomResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_status')
                ->label('Ubah Status')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->url(fn() => route('filament.resepsionis.resources.rooms.manage-status', $this->record))
                ->visible(fn() => in_array($this->record->status, ['available', 'cleaning', 'maintenance'])),
        ];
    }
}
