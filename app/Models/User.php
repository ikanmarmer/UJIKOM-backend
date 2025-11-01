<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'google_id',
        'avatar',
        'verification_code',
        'verification_code_expires_at',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'role' => Role::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        // 1) Selalu izinkan akses ke panel login supaya proses login tidak gagal
        //    (Filament perlu mengakses halaman login / proses login itu sendiri).
        if ($panelId === 'login') {
            return true;
        }

        // 2) Normalisasi role dari model (bisa string, int, atau Backed Enum)
        $role = $this->role;
        if ($role instanceof \BackedEnum) {
            // Enum bertipe backed (PHP 8.1+) — ambil value
            $roleValue = $role->value;
        } else {
            // Sudah string/integer — gunakan langsung
            $roleValue = $role;
        }

        // 3) Perbandingan panel -> role yang diizinkan
        return match ($panelId) {
            'admin' => $roleValue === Role::ADMIN->value,
            'owner' => $roleValue === Role::OWNER->value,
            'resepsionis' => $roleValue === Role::RESEPSIONIST->value,
            default => false,
        };
    }

    // Relations
    public function ownedHotels()
    {
        return $this->hasMany(Hotel::class, 'owner_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function staffAssignments()
    {
        return $this->hasMany(HotelStaff::class);
    }

    public function managedHotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_staff')
            ->withPivot('position', 'is_active')
            ->withTimestamps();
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isOwner()
    {
        return $this->role === 'owner';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isResepsionis()
    {
        return $this->role === 'resepsionis';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

}
