<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ReviewStatus;

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
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'status' => ReviewStatus::class,
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

    // Only update hotel rating when review is approved
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->status === ReviewStatus::APPROVED) {
                $review->hotel->updateRating();
            }
        });

        static::updated(function ($review) {
            if ($review->status === ReviewStatus::APPROVED) {
                $review->hotel->updateRating();
            }
        });

        static::deleted(function ($review) {
            if ($review->status === ReviewStatus::APPROVED) {
                $review->hotel->updateRating();
            }
        });
    }

    // Scope to get only approved reviews
    public function scopeApproved($query)
    {
        return $query->where('status', ReviewStatus::APPROVED);
    }
}
