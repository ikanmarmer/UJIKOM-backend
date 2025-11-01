<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'hotel_id',
        'room_category_id',
        'guest_name',
        'guest_email',
        'check_in_date',
        'check_out_date',
        'nights',
        'number_of_rooms',
        'guests_per_room',
        'total_guests',
        'price_per_night',
        'total_price',
        'status',
        'payment_status',
        'special_requests',
        'expires_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'expires_at' => 'datetime', // TAMBAHAN
        'price_per_night' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'booking_room')
            ->withPivot('check_in_date', 'check_out_date')
            ->withTimestamps();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // TAMBAHAN: Check if booking is expired
    public function isExpired()
    {
        return $this->expires_at && now()->greaterThan($this->expires_at) && $this->status === 'pending';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = 'BK-' . strtoupper(uniqid());
        });
    }
}
