<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Check if user has access to this booking
     * Support both: booking owner and hotel staff
     */
    private function authorizeBooking(Booking $booking): void
    {
        $user = auth()->user();

        // Check if user owns this booking
        if ($booking->user_id === $user->id) {
            return;
        }

        // Check if user is hotel staff
        $hasStaffAccess = $booking->hotel->staff()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasStaffAccess) {
            abort(403, 'Unauthorized access to this invoice.');
        }
    }

    /**
     * Load booking relationships
     */
    private function loadBookingRelations(Booking $booking): Booking
    {
        return $booking->load([
            'hotel',
            'roomCategory',
            'user',
            'rooms',
            'payment'
        ]);
    }

    /**
     * View invoice in browser (web) or return JSON (API)
     */
    public function view(Booking $booking): View|JsonResponse
    {
        $this->authorizeBooking($booking);
        $booking = $this->loadBookingRelations($booking);

        // Jika request dari API, kembalikan JSON
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $booking,
                'invoice_data' => $this->prepareInvoiceData($booking)
            ]);
        }

        // Untuk web, kembalikan view
        return view('invoices.booking', compact('booking'));
    }

    /**
     * Download invoice as PDF
     */
    public function download(Booking $booking): Response
    {
        $this->authorizeBooking($booking);
        $booking = $this->loadBookingRelations($booking);

        $pdf = Pdf::loadView('invoices.booking', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        $filename = "Invoice-{$booking->booking_code}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Print invoice (web only) or return print data (API)
     */
    public function print(Booking $booking): View|JsonResponse
    {
        $this->authorizeBooking($booking);
        $booking = $this->loadBookingRelations($booking);

        // Jika request dari API
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $booking,
                'print_data' => $this->prepareInvoiceData($booking),
                'message' => 'Use this data for printing on client side'
            ]);
        }

        // Untuk web
        return view('invoices.booking', compact('booking'))
            ->with('isPrint', true);
    }

    /**
     * Stream PDF to browser for preview
     */
    public function stream(Booking $booking): Response
    {
        $this->authorizeBooking($booking);
        $booking = $this->loadBookingRelations($booking);

        $pdf = Pdf::loadView('invoices.booking', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->stream("Invoice-{$booking->booking_code}.pdf");
    }

    /**
     * Get invoice data as JSON (for frontend preview) - API Specific
     */
    public function show(Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);
        $booking = $this->loadBookingRelations($booking);

        // Calculate totals
        $subtotal = $booking->total_price;
        $tax = 0; // You can add tax calculation here if needed
        $total = $subtotal + $tax;

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'invoice_data' => [
                    'invoice_number' => $booking->booking_code,
                    'invoice_date' => $booking->created_at->format('d M Y'),
                    'due_date' => $booking->check_in->format('d M Y'),
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_status' => $booking->payment?->status ?? 'unpaid',
                    'booking_status' => $booking->status,
                ]
            ]
        ], 200);
    }

    /**
     * Prepare invoice data for API responses
     */
    private function prepareInvoiceData(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'hotel_name' => $booking->hotel->name,
            'customer_name' => $booking->user->name,
            'check_in' => $booking->check_in->format('d M Y'),
            'check_out' => $booking->check_out->format('d M Y'),
            'total_amount' => number_format($booking->total_amount, 2),
            'status' => $booking->status,
            'payment_status' => $booking->payment?->status ?? 'unpaid',
            'rooms' => $booking->rooms->map(function ($room) {
                return [
                    'room_number' => $room->room_number,
                    'type' => $room->roomCategory->name,
                    'price_per_night' => number_format($room->roomCategory->price_per_night, 2)
                ];
            })
        ];
    }
}
