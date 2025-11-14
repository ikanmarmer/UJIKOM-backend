<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Midtrans\Transaction;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

        public function createPayment(Request $request, $bookingCode)
        {
            $booking = Booking::where('booking_code', $bookingCode)
                ->where('user_id', auth()->id())
                ->with(['hotel', 'roomCategory'])
                ->first();
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found.'
                ], 404);
            }
            if ($booking->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking has already been paid.'
                ], 400);
            }
            DB::beginTransaction();
            try {
                $payment = Payment::where('booking_id', $booking->id)->first();
                if (!$payment) {
                    $payment = Payment::create([
                        'booking_id' => $booking->id,
                        'payment_type' => 'midtrans',
                        'amount' => $booking->total_price,
                        'status' => 'pending',
                        'midtrans_order_id' => $booking->booking_code . '-' . time(),
                    ]);
                }
                $params = [
                    'transaction_details' => [
                        'order_id' => $payment->midtrans_order_id,
                        'gross_amount' => (int) $booking->total_price,
                    ],
                    'customer_details' => [
                        'first_name' => $booking->guest_name,
                        'email' => $booking->guest_email,
                    ],
                    'item_details' => [
                        [
                            'id' => $booking->room_category_id,
                            'price' => (int) $booking->price_per_night,
                            'quantity' => $booking->nights * $booking->number_of_rooms,
                            'name' => $booking->hotel->name . ' - ' . $booking->roomCategory->name,
                        ]
                    ],
                    'callbacks' => [
                        'finish' => url('/payment/finish?booking_code=' . $bookingCode),
                    ]
                ];
                $snapToken = Snap::getSnapToken($params);
                $payment->update([
                    'midtrans_response' => $params,
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'data' => [
                        'snap_token' => $snapToken,
                        'payment' => $payment,
                        'booking' => $booking,
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Payment creation failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment: ' . $e->getMessage()
                ], 500);
            }
        }

    // PERBAIKAN: Handle notification dengan lebih robust
    public function handleNotification(Request $request)
    {
        Log::info('Midtrans Notification Received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'all_data' => $request->all(),
        ]);
        try {
            $notificationBody = $request->getContent();
            $notification = json_decode($notificationBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $notification = $request->all();
            }
            Log::info('Parsed Notification:', ['notification' => $notification]);
            if (!isset($notification['order_id'])) {
                Log::error('Missing order_id in notification');
                return response()->json(['success' => false, 'message' => 'Invalid notification: missing order_id'], 400);
            }
            $orderId = $notification['order_id'];
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? 'accept';
            Log::info('Processing Midtrans Notification', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
            ]);
            $payment = Payment::where('midtrans_order_id', $orderId)->first();
            if (!$payment) {
                Log::error('Payment not found for order_id: ' . $orderId);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.'
                ], 404);
            }
            DB::beginTransaction();
            try {
                $booking = $payment->booking;
                switch ($transactionStatus) {
                    case 'capture':
                        if ($fraudStatus == 'accept') {
                            $this->updatePaymentSuccess($payment, $booking, $notification);
                        } else {
                            $this->updatePaymentFailed($payment, $booking);
                        }
                        break;
                    case 'settlement':
                        $this->updatePaymentSuccess($payment, $booking, $notification);
                        break;
                    case 'pending':
                        $payment->update([
                            'status' => 'pending',
                            'midtrans_transaction_id' => $notification['transaction_id'] ?? null,
                            'midtrans_payment_type' => $notification['payment_type'] ?? null,
                        ]);
                        break;
                    case 'deny':
                    case 'expire':
                    case 'cancel':
                        $this->updatePaymentFailed($payment, $booking);
                        break;
                    default:
                        Log::warning('Unknown transaction status: ' . $transactionStatus);
                        break;
                }
                DB::commit();
                Log::info('Notification processed successfully', ['order_id' => $orderId]);
                return response()->json([
                    'success' => true,
                    'message' => 'Notification handled successfully.'
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to process notification: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process notification.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Invalid notification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Invalid notification.'
            ], 400);
        }
    }

    // Helper method untuk update payment success
    private function updatePaymentSuccess($payment, $booking, $notification)
    {
        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'midtrans_transaction_id' => $notification['transaction_id'] ?? null,
            'midtrans_payment_type' => $notification['payment_type'] ?? null,
            'midtrans_response' => array_merge(
                $payment->midtrans_response ?? [],
                ['notification' => $notification]
            ),
        ]);

        $now = \Carbon\Carbon::now();
        $checkInDate = \Carbon\Carbon::parse($booking->check_in_date)->startOfDay();
        $checkOutDate = \Carbon\Carbon::parse($booking->check_out_date)->startOfDay();

        // Determine booking status based on dates
        if ($now->gte($checkOutDate)) {
            // Past checkout date
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);
            // Set rooms to available
            foreach ($booking->rooms as $room) {
                $room->update(['status' => 'available']);
            }
        } elseif ($now->gte($checkInDate) && $now->lt($checkOutDate)) {
            // Between check-in and check-out
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'active',
            ]);
            // Set rooms to occupied
            foreach ($booking->rooms as $room) {
                $room->update(['status' => 'occupied']);
            }
        } else {
            // Before check-in - set rooms to occupied immediately after payment
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
            // Set rooms to occupied when confirmed
            foreach ($booking->rooms as $room) {
                $room->update(['status' => 'occupied']);
            }
        }

        Log::info('Payment marked as successful', [
            'booking_id' => $booking->id,
            'payment_id' => $payment->id,
            'booking_status' => $booking->status
        ]);
    }

    // Helper method untuk update payment failed
    private function updatePaymentFailed($payment, $booking)
    {
        $payment->update([
            'status' => 'failed',
        ]);

        $booking->update([
            'status' => 'cancelled',
        ]);

        Log::info('Payment marked as failed', [
            'booking_id' => $booking->id,
            'payment_id' => $payment->id
        ]);
    }

    public function checkStatus($bookingCode)
    {
        $booking = Booking::where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->with(['payment', 'hotel', 'roomCategory'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'payment' => $booking->payment,
            ]
        ], 200);
    }

    // PERBAIKAN: Refresh status dengan lebih baik
    public function refreshStatus($bookingCode)
    {
        $booking = Booking::where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->with('payment')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        }

        if (!$booking->payment) {
            return response()->json([
                'success' => true,
                'data' => $booking->fresh(['payment', 'hotel', 'roomCategory']),
                'message' => 'No payment initiated yet.'
            ], 200);
        }

        try {
            $status = Transaction::status($booking->payment->midtrans_order_id);

            Log::info('Midtrans status check response', [
                'order_id' => $booking->payment->midtrans_order_id,
                'status' => $status
            ]);

            DB::beginTransaction();

            if (in_array($status->transaction_status, ['settlement', 'capture'])) {
                $booking->payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'midtrans_transaction_id' => $status->transaction_id,
                    'midtrans_payment_type' => $status->payment_type,
                ]);

                $now = \Carbon\Carbon::now();
                $checkInDate = \Carbon\Carbon::parse($booking->check_in_date)->startOfDay();
                $checkOutDate = \Carbon\Carbon::parse($booking->check_out_date)->startOfDay();

                if ($now->gte($checkOutDate)) {
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => 'completed',
                    ]);
                    foreach ($booking->rooms as $room) {
                        $room->update(['status' => 'available']);
                    }
                } elseif ($now->gte($checkInDate) && $now->lt($checkOutDate)) {
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => 'active',
                    ]);
                    foreach ($booking->rooms as $room) {
                        $room->update(['status' => 'occupied']);
                    }
                } else {
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => 'confirmed',
                    ]);
                    foreach ($booking->rooms as $room) {
                        $room->update(['status' => 'occupied']);
                    }
                }

                Log::info('Payment status refreshed to success', [
                    'booking_code' => $bookingCode
                ]);
            } elseif (in_array($status->transaction_status, ['pending'])) {
                $booking->payment->update([
                    'status' => 'pending',
                    'midtrans_transaction_id' => $status->transaction_id,
                    'midtrans_payment_type' => $status->payment_type,
                ]);

                Log::info('Payment status is still pending', [
                    'booking_code' => $bookingCode
                ]);
            } elseif (in_array($status->transaction_status, ['deny', 'expire', 'cancel'])) {
                $this->updatePaymentFailed($booking->payment, $booking);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $booking->fresh(['payment', 'hotel', 'roomCategory'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to check Midtrans status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }
}
