<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate booking code
        $data['booking_code'] = 'BK-' . strtoupper(uniqid());

        // Set user_id to null for walk-in bookings
        $data['user_id'] = null;

        // Set email to null if empty
        if (empty($data['guest_email'])) {
            $data['guest_email'] = null;
        }

        // Calculate guests_per_room (average untuk compatibility)
        $roomDetails = $data['room_details'] ?? [];
        $guestsCounts = array_column($roomDetails, 'guests_count');
        $data['guests_per_room'] = !empty($guestsCounts)
            ? (int) round(array_sum($guestsCounts) / count($guestsCounts))
            : 1;

        // Set expiry for pending bookings
        if ($data['status'] === 'pending') {
            $data['expires_at'] = now()->addHour();
        }

        // Remove temporary fields
        unset($data['payment_method']);
        unset($data['summary_hotel']);
        unset($data['summary_category']);
        unset($data['summary_dates']);
        unset($data['summary_rooms']);
        unset($data['summary_room_details']);
        unset($data['summary_total']);
        unset($data['max_guests_allowed']);
        unset($data['room_details']); // We'll use this separately

        return $data;
    }

    protected function afterCreate(): void
    {
        $booking = $this->record;
        $data = $this->form->getRawState();
        $roomDetails = $data['room_details'] ?? [];

        DB::beginTransaction();
        try {
            // For confirmed/paid bookings, create payment record and assign rooms
            if ($booking->status === 'confirmed' && $booking->payment_status === 'paid') {
                Payment::create([
                    'booking_id' => $booking->id,
                    'transaction_id' => 'TRX-' . strtoupper(uniqid()),
                    'payment_type' => 'offline',
                    'amount' => $booking->total_price,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                // Auto-assign rooms with specific guest counts
                $this->autoAssignRoomsWithGuestCounts($booking, $roomDetails);
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Booking Berhasil Dibuat')
                ->body("Booking {$booking->booking_code} telah dibuat untuk {$booking->guest_name}")
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking creation failed: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Gagal Membuat Booking')
                ->body('Terjadi kesalahan saat membuat booking.')
                ->send();
        }
    }

    private function autoAssignRoomsWithGuestCounts($booking, array $roomDetails): void
    {
        $availableRooms = $booking->roomCategory
            ->getAvailableRoomsForPeriod(
                $booking->check_in_date->toDateString(),
                $booking->check_out_date->toDateString(),
                $booking->number_of_rooms
            );

        if ($availableRooms->count() >= $booking->number_of_rooms) {
            foreach ($availableRooms->take($booking->number_of_rooms) as $index => $room) {
                // Get guest count for this specific room
                $guestsCount = $roomDetails[$index]['guests_count'] ?? $booking->guests_per_room;

                $booking->rooms()->attach($room->id, [
                    'check_in_date' => $booking->check_in_date,
                    'check_out_date' => $booking->check_out_date,
                    'guests_count' => $guestsCount,
                ]);

                $room->update(['status' => 'occupied']);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
