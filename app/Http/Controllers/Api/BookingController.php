<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingVerificationMail;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomCategory;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class BookingController extends Controller
{
    // Send Verification Code for Booking
    public function sendBookingVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        cache()->put('booking_verification_' . $request->email, $verificationCode, now()->addMinutes(10));

        $userName = explode('@', $request->email)[0];

        Mail::to($request->email)->send(new BookingVerificationMail($userName, $verificationCode));

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.'
        ], 200);
    }

    // Verify Booking Code
    public function verifyBookingCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $cachedCode = cache()->get('booking_verification_' . $request->email);

        if (!$cachedCode) {
            return response()->json(['success' => false, 'message' => 'Verification code has expired.'], 400);
        }

        if ($cachedCode !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code.'], 400);
        }

        cache()->put('booking_verified_' . $request->email, true, now()->addMinutes(30));

        return response()->json(['success' => true, 'message' => 'Email verified successfully.'], 200);
    }

    // Create Booking dengan Auto Room Selection (Berurutan)
    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_category_id' => 'required|exists:room_categories,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_rooms' => 'required|integer|min:1|max:6',
            'guests_per_room' => 'required|integer|min:1|max:3',
            'special_requests' => 'nullable|string',
        ]);

        // Verify email token
        if (!cache()->get('booking_verified_' . $request->guest_email)) {
            return response()->json(['success' => false, 'message' => 'Please verify your email before booking.'], 403);
        }

        $hotel = Hotel::findOrFail($request->hotel_id);
        $roomCategory = RoomCategory::findOrFail($request->room_category_id);

        // Validasi tambahan: Cek apakah jumlah rooms yang diminta tidak melebihi total rooms di kategori
        if ($request->number_of_rooms > $roomCategory->total_rooms) {
            return response()->json([
                'success' => false,
                'message' => "The maximum number of rooms available for {$roomCategory->name} is {$roomCategory->total_rooms}."
            ], 400);
        }

        // Validasi: Cek apakah guests_per_room tidak melebihi max_guests
        if ($request->guests_per_room > $roomCategory->max_guests) {
            return response()->json([
                'success' => false,
                'message' => "Maximum guests per room for {$roomCategory->name} is {$roomCategory->max_guests}."
            ], 400);
        }

        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $nights = $checkIn->diffInDays($checkOut);

        // Dapatkan jumlah rooms yang available untuk periode tersebut
        $availableRoomsCount = $roomCategory->getAvailableRoomsCountForPeriod(
            $checkIn->toDateString(),
            $checkOut->toDateString()
        );

        // Validasi: Cek apakah cukup rooms available
        if ($availableRoomsCount < $request->number_of_rooms) {
            $message = $availableRoomsCount == 0
                ? "No rooms available for {$roomCategory->name} on the selected dates."
                : "Only {$availableRoomsCount} room(s) available for {$roomCategory->name} on the selected dates. Please adjust your booking.";

            return response()->json([
                'success' => false,
                'message' => $message,
                'available_rooms' => $availableRoomsCount
            ], 400);
        }

        // Ambil rooms secara berurutan berdasarkan room_number dan floor
        $availableRooms = $roomCategory->getAvailableRoomsForPeriod(
            $checkIn->toDateString(),
            $checkOut->toDateString(),
            $request->number_of_rooms
        );

        // Double check: Pastikan kita mendapatkan cukup rooms
        if ($availableRooms->count() < $request->number_of_rooms) {
            return response()->json([
                'success' => false,
                'message' => 'Room availability changed. Please try again.',
                'available_rooms' => $availableRooms->count()
            ], 400);
        }

        DB::beginTransaction();
        try {
            $totalGuests = $request->number_of_rooms * $request->guests_per_room;
            $totalPrice = $roomCategory->price_per_night * $nights * $request->number_of_rooms;

            $booking = Booking::create([
                'user_id' => auth()->id() ?? null,
                'hotel_id' => $hotel->id,
                'room_category_id' => $roomCategory->id,
                'guest_name' => $request->guest_name,
                'guest_email' => $request->guest_email,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'nights' => $nights,
                'number_of_rooms' => $request->number_of_rooms,
                'guests_per_room' => $request->guests_per_room,
                'total_guests' => $totalGuests,
                'price_per_night' => $roomCategory->price_per_night,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'special_requests' => $request->special_requests,
                'booking_code' => 'BK' . strtoupper(uniqid()),
                'expires_at' => now()->addMinutes(15),
            ]);

            foreach ($availableRooms as $room) {
                $booking->rooms()->attach($room->id, [
                    'check_in_date' => $checkIn->toDateString(),
                    'check_out_date' => $checkOut->toDateString(),
                ]);
            }

            DB::commit();

            cache()->forget('booking_verified_' . $request->guest_email);

            // Load full relationship dengan room_category untuk mendapatkan images
            $booking->load([
                'hotel',
                'roomCategory',
                'rooms' => function ($query) {
                    $query->select('rooms.id', 'rooms.room_category_id', 'rooms.room_number', 'rooms.floor', 'rooms.status')
                        ->orderBy('floor', 'asc')
                        ->orderBy('room_number', 'asc');
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully. Please complete payment within 15 minutes.',
                'data' => $booking,
                'expires_at' => $booking->expires_at,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Booking creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to create booking.'], 500);
        }
    }
    public function index(Request $request)
    {
        $query = Booking::with([
            'hotel.city',
            'roomCategory', // Images dari room_category
            'payment',
            'rooms' => function ($query) {
                $query->select('rooms.id', 'rooms.room_category_id', 'rooms.room_number', 'rooms.floor', 'rooms.status')
                    ->orderBy('floor', 'asc')
                    ->orderBy('room_number', 'asc');
            }
        ])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], 200);
    }

    public function show($bookingCode)
    {
        $booking = Booking::with([
            'hotel.city',
            'roomCategory.amenities', // Room category sudah include images
            'rooms' => function ($query) {
                $query->select('rooms.id', 'rooms.room_category_id', 'rooms.room_number', 'rooms.floor', 'rooms.status')
                    ->orderBy('floor', 'asc')
                    ->orderBy('room_number', 'asc');
            },
            'payment',
            'review'
        ])
            ->where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $booking], 200);
    }

    public function cancel($bookingCode)
    {
        $booking = Booking::where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['success' => false, 'message' => 'This booking cannot be cancelled.'], 400);
        }

        DB::beginTransaction();
        try {
            $booking->update(['status' => 'cancelled']);

            if ($booking->payment_status === 'paid') {
                $booking->update(['payment_status' => 'refunded']);
            }

            // Release rooms (set back to available if status was occupied)
            foreach ($booking->rooms as $room) {
                if ($room->status === 'occupied') {
                    $room->update(['status' => 'available']);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Booking cancelled successfully.', 'data' => $booking], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Booking cancel failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to cancel booking.'], 500);
        }
    }
}
