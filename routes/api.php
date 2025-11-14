<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\AmenityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Public routes
Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/verify-email', 'verifyEmail');
        Route::post('/resend-verification', 'resendVerificationCode');
        Route::post('/login', 'login');
        // Google OAuth
        Route::get('/google', 'redirectToGoogle');
        Route::get('/google/callback', 'handleGoogleCallback');
    });
// Hotels & Search
Route::prefix('hotels')
    ->controller(HotelController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}/images', 'getHotelImages');
        Route::get('/price-range/all', 'getPriceRange');
        Route::post('/{hotelId}/check-availability', 'checkAvailability')
            ->whereNumber('hotelId');
        Route::post('/quick-availability/check', 'quickAvailabilityCheck');
        Route::get('/{slug}', 'show');
    });
Route::get('/search/suggestions', [HotelController::class, 'searchSuggestions']);
// Cities
Route::prefix('cities')
    ->controller(CityController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/search', 'search');
        Route::get('/{slug}', 'show');
    });
// Amenities
Route::get('/amenities', [AmenityController::class, 'index']);
// Reviews (public - get approved reviews only)
Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'index']);
// Booking Verification (no auth required)
Route::prefix('bookings')
    ->controller(BookingController::class)
    ->group(function () {
        Route::post('/send-verification', 'sendBookingVerification')
            ->middleware('throttle:5,1');
        Route::post('/verify-code', 'verifyBookingCode');
    });
// Midtrans Notification (public webhook)
Route::post('/payments/midtrans/notification', [PaymentController::class, 'handleNotification']);
// Invoice routes
Route::prefix('invoices')
    ->controller(InvoiceController::class)
    ->group(function () {
        Route::get('/bookings/{booking}', 'show');
        Route::get('/bookings/{booking}/view', 'view');
        Route::get('/bookings/{booking}/download', 'download');
        Route::get('/bookings/{booking}/stream', 'stream');
    });
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::prefix('bookings')
        ->controller(BookingController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{bookingCode}', 'show');
            Route::put('/{bookingCode}', 'update');
            Route::post('/{bookingCode}/cancel', 'cancel');
        });
    Route::prefix('payments')
        ->controller(PaymentController::class)
        ->group(function () {
            Route::post('/bookings/{bookingCode}', 'createPayment');
            Route::get('/bookings/{bookingCode}/status', 'checkStatus');
            Route::post('/bookings/{bookingCode}/refresh', 'refreshStatus');
        });
    Route::prefix('reviews')->controller(ReviewController::class)->group(function () {
        Route::get('/can-review/{hotelId}', 'canReview');
        Route::get('/pending', 'pending');
        Route::get('/my-reviews', 'myReviews');
        Route::post('/', 'store');
        Route::post('/{id}', 'update'); // Using POST for multipart/form-data
        Route::delete('/{id}', 'destroy');
    });

});
