<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    // Create review
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or not completed.'
            ], 404);
        }

        if ($booking->review) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this booking.'
            ], 400);
        }

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $images[] = $path;
            }
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'hotel_id' => $booking->hotel_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => $images,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully.',
            'data' => $review->load('user')
        ], 201);
    }

    // Get hotel reviews
    public function index($hotelId)
    {
        $reviews = Review::where('hotel_id', $hotelId)
            ->with('user', 'booking.roomCategory')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ], 200);
    }
}