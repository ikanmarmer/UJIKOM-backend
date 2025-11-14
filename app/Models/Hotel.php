<?php

namespace App\Models;

use App\Enums\ReviewStatus;
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

    /**
     * Accessor untuk image URLs
     * Menangani berbagai format data images dan memberikan fallback
     */
    public function getImageUrlsAttribute()
    {
        $images = $this->attributes['images'] ?? null;

        // Jika tidak ada images, return empty array (frontend akan handle fallback)
        if (!$images) {
            return [];
        }

        // Jika string, coba decode JSON
        if (is_string($images)) {
            // Cek apakah ini JSON array
            if (str_starts_with($images, '[')) {
                $decoded = json_decode($images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $images = $decoded;
                } else {
                    // Jika JSON decode gagal, treat sebagai single image path
                    $images = [$images];
                }
            } else {
                // Single image path
                $images = [$images];
            }
        }

        // Jika bukan array atau array kosong, return empty
        if (!is_array($images) || empty($images)) {
            return [];
        }

        // Filter dan map images
        return array_values(array_filter(array_map(function ($image) {
            // Skip jika null atau empty
            if (empty($image)) {
                return null;
            }

            // Jika sudah URL lengkap, return as is
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }

            // Cek apakah file exists di storage
            if (Storage::disk('public')->exists($image)) {
                return asset('storage/' . $image);
            }

            // Jika file tidak ada, return null (akan difilter)
            return null;
        }, $images)));
    }

    /**
     * Accessor untuk thumbnail (image pertama)
     * Tidak perlu fallback di sini, frontend akan handle
     */
    public function getThumbnailAttribute()
    {
        $urls = $this->image_urls;
        return $urls[0] ?? null;
    }

    public function updateRating()
    {
        // Lebih efisien karena langsung query ke database
        $stats = $this->reviews()
            ->where('status', ReviewStatus::APPROVED)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        if ($stats->avg_rating !== null) {
            $this->attributes['average_rating'] = number_format((float) $stats->avg_rating, 1, '.', '');
        }

        $this->total_reviews = $stats->total ?? 0;
        $this->save();
    }
}
