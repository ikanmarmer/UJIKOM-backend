<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateBookingStatus extends Command
{
    protected $signature = 'booking:update-status';
    protected $description = 'Update booking status based on check-in and check-out dates';

    public function handle()
    {
        $now = Carbon::now();
        $updated = 0;

        // Update confirmed bookings to active when check-in date arrives
        $confirmedBookings = Booking::where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->whereDate('check_in_date', '<=', $now->toDateString())
            ->whereDate('check_out_date', '>', $now->toDateString())
            ->with('rooms')
            ->get();

        foreach ($confirmedBookings as $booking) {
            $booking->update(['status' => 'active']);
            foreach ($booking->rooms as $room) {
                $room->update(['status' => 'occupied']);
            }
            $updated++;
        }

        // Update active bookings to completed when check-out date passes
        $activeBookings = Booking::where('status', 'active')
            ->whereDate('check_out_date', '<=', $now->toDateString())
            ->with('rooms')
            ->get();

        foreach ($activeBookings as $booking) {
            $booking->update(['status' => 'completed']);
            foreach ($booking->rooms as $room) {
                $room->update(['status' => 'available']);
            }
            $updated++;
        }

        $this->info("Updated {$updated} booking(s) status.");
        return 0;
    }
}
