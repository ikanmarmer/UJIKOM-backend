<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn($record) => in_array($record->status, ['pending', 'confirmed'])),

            Action::make('checkin')
                ->label('Check-in')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Proses Check-in')
                ->modalDescription(fn() => "Apakah Anda yakin ingin check-in tamu {$this->record->guest_name}?")
                ->action(function () {
                    return redirect()->route('filament.resepsionis.resources.bookings.checkin', $this->record);
                })
                ->visible(
                    fn() =>
                    $this->record->status === 'confirmed' &&
                    $this->record->payment_status === 'paid' &&
                    today()->gte($this->record->check_in_date)
                ),

            Action::make('checkout')
                ->label('Check-out')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Proses Check-out')
                ->modalDescription(fn() => "Apakah Anda yakin ingin check-out tamu {$this->record->guest_name}?")
                ->action(function () {
                    return redirect()->route('filament.resepsionis.resources.bookings.checkout', $this->record);
                })
                ->visible(fn() => $this->record->status === 'active'),

            Action::make('download_invoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn() => route('resepsionis.bookings.invoice.download', $this->record->id))
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->payment_status === 'paid'),

            Action::make('print_invoice')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn() => route('resepsionis.bookings.invoice.print', $this->record->id))
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->payment_status === 'paid'),

            Action::make('cancel')
                ->label('Batalkan Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Booking')
                ->modalDescription('Apakah Anda yakin ingin membatalkan booking ini?')
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);

                    // Release rooms
                    foreach ($this->record->rooms as $room) {
                        $room->update(['status' => 'available']);
                    }

                    Notification::make()
                        ->success()
                        ->title('Booking Dibatalkan')
                        ->send();
                })
                ->visible(fn() => in_array($this->record->status, ['pending', 'confirmed'])),
        ];
    }
}
