<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'hotel_id',
        'rating',
        'comment',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            $review->hotel->updateRating();
        });

        static::updated(function ($review) {
            $review->hotel->updateRating();
        });

        static::deleted(function ($review) {
            $review->hotel->updateRating();
        });
    }
}