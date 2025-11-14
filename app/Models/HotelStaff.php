<?php

// File: app/Models/HotelStaff.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelStaff extends Model
{
    use HasFactory;

    protected $table = 'hotel_staff';

    protected $fillable = [
        'hotel_id',
        'user_id',
        'is_active',
        'assigned_at',
        'terminated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    // Relationships
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function terminate()
    {
        $this->update([
            'is_active' => false,
            'terminated_at' => now(),
        ]);
    }

    public function reactivate()
    {
        $this->update([
            'is_active' => true,
            'terminated_at' => null,
        ]);
    }
}
