<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'transaction_id',
        'payment_type',
        'amount',
        'status',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'paid_at',
        'midtrans_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'midtrans_response' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->transaction_id = 'TRX-' . strtoupper(uniqid());
        });
    }
}