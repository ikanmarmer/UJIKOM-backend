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
        'first_image_url',
        'available_rooms_count' // Accessor untuk available rooms
    ];

    // Accessor untuk available_rooms_count (tanpa parameter)
    public function getAvailableRoomsCountAttribute()
    {
        return $this->rooms()->where('status', 'available')->count();
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

    // Accessor untuk mendapatkan first image
    public function getFirstImageUrlAttribute()
    {
        return $this->images && count($this->images) > 0 ? $this->images[0] : null;
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
