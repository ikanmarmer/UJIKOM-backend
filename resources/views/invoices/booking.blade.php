{{-- resources/views/invoices/booking.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $booking->booking_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 40px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }

        .company-info h1 {
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .company-info p {
            color: #666;
            font-size: 14px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-info h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .invoice-info p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-paid {
            background: #10b981;
            color: white;
        }

        .status-unpaid {
            background: #f59e0b;
            color: white;
        }

        .details-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        .detail-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-box h3 {
            color: #2563eb;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .detail-box p {
            color: #374151;
            font-size: 14px;
            margin: 8px 0;
        }

        .detail-box strong {
            color: #111827;
        }

        .booking-details {
            margin: 30px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        thead {
            background: #2563eb;
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            font-weight: 600;
            font-size: 14px;
        }

        td {
            color: #374151;
            font-size: 14px;
        }

        .total-section {
            margin-top: 30px;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 10px 0;
        }

        .total-row.grand-total {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .total-row span:first-child {
            margin-right: 30px;
            color: #6b7280;
        }

        .total-row.grand-total span {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        .rooms-list {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .room-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .room-item:last-child {
            border-bottom: none;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }

            @page {
                margin: 2cm;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $booking->hotel->name }}</h1>
                <p>{{ $booking->hotel->address }}</p>
                <p>Tel: {{ $booking->hotel->phone }}</p>
                <p>Email: {{ $booking->hotel->email }}</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Booking Code:</strong> {{ $booking->booking_code }}</p>
                <p><strong>Tanggal:</strong> {{ $booking->created_at->format('d M Y') }}</p>
                <span class="status-badge status-{{ $booking->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
                    {{ $booking->payment_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
                </span>
            </div>
        </div>

        <!-- Customer & Booking Info -->
        <div class="details-section">
            <div class="detail-box">
                <h3>Informasi Tamu</h3>
                <p><strong>Nama:</strong> {{ $booking->guest_name }}</p>
                <p><strong>Email:</strong> {{ $booking->guest_email }}</p>
                @if($booking->user)
                    <p><strong>Telepon:</strong> {{ $booking->user->phone ?? '-' }}</p>
                @endif
            </div>

            <div class="detail-box">
                <h3>Detail Pemesanan</h3>
                <p><strong>Check-in:</strong> {{ $booking->check_in_date->format('d M Y') }}</p>
                <p><strong>Check-out:</strong> {{ $booking->check_out_date->format('d M Y') }}</p>
                <p><strong>Durasi:</strong> {{ $booking->nights }} malam</p>
                <p><strong>Status:</strong> {{ ucfirst($booking->status) }}</p>
            </div>
        </div>

        <!-- Booking Details Table -->
        <div class="booking-details">
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $booking->roomCategory->name }}</strong><br>
                            <small>{{ $booking->hotel->name }}</small>
                        </td>
                        <td style="text-align: center;">
                            {{ $booking->number_of_rooms }} kamar × {{ $booking->nights }} malam
                        </td>
                        <td style="text-align: right;">
                            IDR {{ number_format($booking->price_per_night, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right;">
                            IDR {{ number_format($booking->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <strong>Tamu:</strong> {{ $booking->total_guests }} tamu
                            ({{ $booking->guests_per_room }} tamu per kamar)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Rooms List (if assigned) -->
        @if($booking->rooms->count() > 0)
            <div class="rooms-list">
                <h3 style="margin-bottom: 10px; color: #2563eb;">Kamar yang Ditugaskan:</h3>
                @foreach($booking->rooms->groupBy('floor') as $floor => $rooms)
                    <div class="room-item">
                        <strong>Lantai {{ $floor }}:</strong>
                        {{ $rooms->pluck('room_number')->join(', ') }}
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Payment Info -->
        @if($booking->payment)
            <div class="detail-box" style="margin: 20px 0;">
                <h3>Informasi Pembayaran</h3>
                <p><strong>Metode:</strong> {{ ucfirst($booking->payment->payment_type) }}</p>
                @if($booking->payment->midtrans_payment_type)
                    <p><strong>Tipe Pembayaran:</strong> {{ ucfirst(str_replace('_', ' ', $booking->payment->midtrans_payment_type)) }}</p>
                @endif
                @if($booking->payment->paid_at)
                    <p><strong>Dibayar pada:</strong> {{ $booking->payment->paid_at->format('d M Y, H:i') }}</p>
                @endif
                @if($booking->payment->midtrans_transaction_id)
                    <p><strong>ID Transaksi:</strong> {{ $booking->payment->midtrans_transaction_id }}</p>
                @endif
            </div>
        @endif

        <!-- Total -->
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>IDR {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Pajak & Service (0%):</span>
                <span>IDR 0</span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>IDR {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Special Requests -->
        @if($booking->special_requests)
            <div class="detail-box" style="margin: 30px 0;">
                <h3>Catatan & Permintaan Khusus</h3>
                <p style="white-space: pre-line;">{{ $booking->special_requests }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih telah memilih {{ $booking->hotel->name }}</p>
            <p>Invoice ini dicetak pada {{ now()->format('d M Y, H:i') }}</p>
            <p style="margin-top: 20px;">
                Dokumen ini adalah bukti pembayaran yang sah dan dicetak oleh sistem.<br>
                Untuk pertanyaan, hubungi {{ $booking->hotel->phone }} atau {{ $booking->hotel->email }}
            </p>
        </div>
    </div>
</body>
</html>
