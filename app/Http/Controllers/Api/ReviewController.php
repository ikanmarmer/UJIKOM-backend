<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use App\Enums\ReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    // Check if user can review hotel
    public function canReview($hotelId)
    {
        $hasCompletedBooking = Booking::where('user_id', auth()->id())
            ->where('hotel_id', $hotelId)
            ->where('status', 'completed')
            ->exists();

        $existingReview = Review::where('user_id', auth()->id())
            ->where('hotel_id', $hotelId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'can_review' => $hasCompletedBooking,
                'has_review' => $existingReview !== null,
                'review' => $existingReview ? $existingReview->load('user') : null,
            ]
        ], 200);
    }

    // Create review
    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Get completed booking
        $booking = Booking::where('user_id', auth()->id())
            ->where('hotel_id', $request->hotel_id)
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review hotels where you have completed bookings.'
            ], 403);
        }

        // Check if user already has a review for this hotel
        $existingReview = Review::where('user_id', auth()->id())
            ->where('hotel_id', $request->hotel_id)
            ->first();

        if ($existingReview && $existingReview->status !== ReviewStatus::REJECTED) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this hotel.'
            ], 400);
        }

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $images[] = $path;
            }
        }

        // If rejected review exists, update it instead of creating new
        if ($existingReview && $existingReview->status === ReviewStatus::REJECTED) {
            // Delete old images if new ones uploaded
            if (!empty($images) && $existingReview->images) {
                foreach ($existingReview->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            $existingReview->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
                'images' => !empty($images) ? $images : $existingReview->images,
                'status' => ReviewStatus::PENDING,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review resubmitted successfully. Waiting for approval.',
                'data' => $existingReview->load('user')
            ], 200);
        }

        // Create new review
        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'hotel_id' => $request->hotel_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => $images,
            'status' => ReviewStatus::PENDING,
        ]);

        $review->load('user');
        
        // Add image URLs
        if ($review->images) {
            $review->image_urls = array_map(function ($image) {
                return asset('storage/' . $image);
            }, $review->images);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. Waiting for approval.',
            'data' => $review
        ], 201);
    }

    // Update review
    public function update(Request $request, $id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.'
            ], 404);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'existing_images' => 'nullable|string',
        ]);

        $images = [];
        
        // Keep existing images that weren't removed
        if ($request->has('existing_images') && $request->existing_images) {
            $images = is_array($request->existing_images) 
                ? $request->existing_images 
                : json_decode($request->existing_images, true) ?? [];
        }
        
        // Add new uploaded images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $images[] = $path;
            }
        }
        
        // Delete removed images
        $oldImages = $review->images ?? [];
        $removedImages = array_diff($oldImages, $images);
        foreach ($removedImages as $removedImage) {
            Storage::disk('public')->delete($removedImage);
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => $images,
            'status' => ReviewStatus::PENDING,
        ]);

        $review->load('user');
        
        // Add image URLs
        if ($review->images) {
            $review->image_urls = array_map(function ($image) {
                return asset('storage/' . $image);
            }, $review->images);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully. Waiting for approval.',
            'data' => $review
        ], 200);
    }

    // Get user's pending reviews
    public function pending()
    {
        $reviews = Review::where('user_id', auth()->id())
            ->where('status', ReviewStatus::PENDING)
            ->with(['user', 'hotel'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add full image URLs
        $reviews->transform(function ($review) {
            if ($review->images) {
                $review->image_urls = array_map(function ($image) {
                    return asset('storage/' . $image);
                }, $review->images);
            }
            return $review;
        });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ], 200);
    }

    // Get hotel reviews (only approved)
    public function index($hotelId)
    {
        $reviews = Review::where('hotel_id', $hotelId)
            ->where('status', ReviewStatus::APPROVED)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Add full image URLs
        $reviews->getCollection()->transform(function ($review) {
            if ($review->images) {
                $review->image_urls = array_map(function ($image) {
                    return asset('storage/' . $image);
                }, $review->images);
            }
            return $review;
        });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ], 200);
    }

    // Get user's all reviews
    public function myReviews()
    {
        $reviews = Review::where('user_id', auth()->id())
            ->with(['user', 'hotel'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add full image URLs
        $reviews->transform(function ($review) {
            if ($review->images) {
                $review->image_urls = array_map(function ($image) {
                    return asset('storage/' . $image);
                }, $review->images);
            }
            return $review;
        });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ], 200);
    }

    // Delete review
    public function destroy($id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.'
            ], 404);
        }

        // Delete images
        if ($review->images) {
            foreach ($review->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.'
        ], 200);
    }
}
