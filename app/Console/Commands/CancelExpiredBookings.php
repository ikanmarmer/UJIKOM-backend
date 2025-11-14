<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    protected $signature = 'bookings:cancel-expired';
    protected $description = 'Cancel bookings that have expired (unpaid after 1 hour)';

    public function handle()
    {
        $expiredBookings = Booking::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'cancelled']);
            $count++;
        }

        $this->info("Cancelled {$count} expired bookings.");
        return 0;
    }
}
