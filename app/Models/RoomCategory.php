<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RoomCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'type',
        'description',
        'max_guests',
        'price_per_night',
        'total_rooms',
        'size',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'price_per_night' => 'decimal:2',
        'size' => 'decimal:2',
    ];

    protected $appends = [
        'image_urls',
        'thumbnail',
        'available_rooms_count' // Accessor untuk available rooms
    ];

    // Accessor untuk available_rooms_count (tanpa parameter)
    public function getAvailableRoomsCountAttribute()
    {
        return $this->rooms()->where('status', 'available')->count();
    }

    /**
     * Accessor untuk image URLs
     * Sama seperti Hotel model untuk konsistensi
     */
    public function getImageUrlsAttribute()
    {
        $images = $this->attributes['images'] ?? null;
        
        // Jika tidak ada images, return empty array
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
            if (\Storage::disk('public')->exists($image)) {
                return asset('storage/' . $image);
            }
            
            // Jika file tidak ada, return null (akan difilter)
            return null;
        }, $images)));
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_room_category')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Accessor untuk thumbnail (image pertama)
     */
    public function getThumbnailAttribute()
    {
        $urls = $this->image_urls;
        return $urls[0] ?? null;
    }

    // Method untuk sync total_rooms
    public function updateTotalRooms()
    {
        $this->total_rooms = $this->rooms()->count();
        $this->save();
    }

    // Get available rooms count untuk periode tertentu (ganti nama method)
    public function getAvailableRoomsCountForPeriod($checkIn, $checkOut)
    {
        $bookedRoomIds = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->where('bookings.room_category_id', $this->id)
            ->whereIn('bookings.status', ['confirmed', 'active'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('booking_room.check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('booking_room.check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('booking_room.check_in_date', '<=', $checkIn)
                            ->where('booking_room.check_out_date', '>=', $checkOut);
                    });
            })
            ->pluck('booking_room.room_id')
            ->unique();

        return $this->rooms()
            ->whereNotIn('id', $bookedRoomIds)
            ->where('status', 'available')
            ->count();
    }

    // Get available rooms untuk periode tertentu (ganti nama method)
    public function getAvailableRoomsForPeriod($checkIn, $checkOut, $limit = null)
    {
        $bookedRoomIds = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->where('bookings.room_category_id', $this->id)
            ->whereIn('bookings.status', ['confirmed', 'active'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('booking_room.check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('booking_room.check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('booking_room.check_in_date', '<=', $checkIn)
                            ->where('booking_room.check_out_date', '>=', $checkOut);
                    });
            })
            ->pluck('booking_room.room_id')
            ->unique();

        $query = $this->rooms()
            ->whereNotIn('id', $bookedRoomIds)
            ->where('status', 'available')
            ->orderBy('floor', 'asc')
            ->orderBy('room_number', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    // Boot method untuk RoomCategory
    protected static function boot()
    {
        parent::boot();

        // Auto-update total_rooms ketika room category dibuat (jika ada rooms yang langsung di-assign)
        static::created(function ($category) {
            $category->updateTotalRooms();
        });
    }
}
