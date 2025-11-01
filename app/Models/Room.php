<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_category_id',
        'room_number',
        'floor',
        'status',
    ];

    protected $appends = [
        'category_images'
    ];

    protected static function boot()
    {
        parent::boot();

        // Event ketika room dibuat
        static::created(function ($room) {
            if ($room->roomCategory) {
                $room->roomCategory->updateTotalRooms();
            }
        });

        // Event ketika room di-update
        static::updated(function ($room) {
            // Update jika room_category_id berubah
            if ($room->isDirty('room_category_id')) {
                // Update kategori lama
                $originalCategory = RoomCategory::find($room->getOriginal('room_category_id'));
                if ($originalCategory) {
                    $originalCategory->updateTotalRooms();
                }
                // Update kategori baru
                if ($room->roomCategory) {
                    $room->roomCategory->updateTotalRooms();
                }
            }

            // Update jika status berubah (optional, tergantung kebutuhan)
            if ($room->isDirty('status') && $room->roomCategory) {
                $room->roomCategory->updateTotalRooms();
            }
        });

        // Event ketika room di-delete
        static::deleted(function ($room) {
            if ($room->roomCategory) {
                $room->roomCategory->updateTotalRooms();
            }
        });

        // // Event ketika room di-restore (jika menggunakan soft delete)
        // static::restored(function ($room) {
        //     if ($room->roomCategory) {
        //         $room->roomCategory->updateTotalRooms();
        //     }
        // });
    }

    public function getCategoryImagesAttribute()
    {
        return $this->roomCategory
            ? $this->roomCategory->images
            : [];
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_room')
            ->withPivot('check_in_date', 'check_out_date')
            ->withTimestamps();
    }

    public function isAvailable($checkIn, $checkOut)
    {
        if ($this->status !== 'available') {
            return false;
        }

        return !$this->bookings()
            ->whereIn('status', ['confirmed', 'active'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('booking_room.check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('booking_room.check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('booking_room.check_in_date', '<=', $checkIn)
                            ->where('booking_room.check_out_date', '>=', $checkOut);
                    });
            })
            ->exists();
    }
}
