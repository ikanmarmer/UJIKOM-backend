<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn($record) => $record->status === 'pending'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add payment method for display
        $data['payment_method'] = $this->record->payment?->payment_type === 'offline' ? 'offline' : 'online';

        return $data;
    }
}
