<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'address',
        'phone',
        'email',
        'average_rating',
        'total_reviews',
        'status',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'average_rating' => 'decimal:1',
        'star_rating' => 'integer',
    ];

    protected $appends = ['image_urls', 'thumbnail'];

    // Relationships
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function roomCategories()
    {
        return $this->hasMany(RoomCategory::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'hotel_amenity')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function staff()
    {
        return $this->hasMany(HotelStaff::class);
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'hotel_staff')
            ->wherePivot('position', 'manager')
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    public function receptionists()
    {
        return $this->belongsToMany(User::class, 'hotel_staff')
            ->wherePivot('position', 'resepsionis')
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    // Accessor untuk image URLs
    public function getImageUrlsAttribute()
    {
        if (!$this->images || !is_array($this->images)) {
            return [asset('images/placeholder-hotel.jpg')];
        }

        return array_map(function ($image) {
            // Jika sudah full URL (http/https)
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }

            // Jika path storage
            if (Storage::disk('public')->exists($image)) {
                return Storage::url($image);
            }

            // Jika path public
            return asset($image);
        }, $this->images);
    }

    // Accessor untuk thumbnail (image pertama)
    public function getThumbnailAttribute()
    {
        $urls = $this->image_urls;
        return $urls[0] ?? asset('images/placeholder-hotel.jpg');
    }

    public function updateRating()
    {
        $this->average_rating = $this->reviews()->avg('rating') ?? 0;
        $this->total_reviews = $this->reviews()->count();
        $this->save();
    }
}
