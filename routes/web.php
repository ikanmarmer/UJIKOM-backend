<?php

use Illuminate\Support\Facades\Route;
use App\Enums\Role;

Route::get('/', action: function () {
    if (auth()->check()) {
        $user = auth()->user();

        return match ($user->role) {
            Role::ADMIN => redirect()->to('/admin'),
            Role::OWNER => redirect()->to('/owner'),
            Role::RESEPSIONIST => redirect()->to('/resepsionis'),
        };
    }

    // Jika belum login, redirect ke halaman login
    return redirect()->to('/login');
})->name('home');
