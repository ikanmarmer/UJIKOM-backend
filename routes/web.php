<?php

use Illuminate\Support\Facades\Route;
use App\Enums\Role;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        return match ($user->role) {
            Role::ADMIN => redirect()->to('/admin'),
            Role::OWNER => redirect()->to('/owner'),
            Role::RESEPSIONIS => redirect()->to('/resepsionis'),
            default => redirect()->to('/login'),
        };
    }

    return redirect()->to('/login');
})->name('home');

// Resepsionis Invoice Routes
Route::middleware(['auth', 'web', 'token.from.query'])->prefix('resepsionis/bookings/invoice')->group(function () {
    Route::get('/{booking}/view', [InvoiceController::class, 'view'])
        ->name('resepsionis.bookings.invoice.view');

    Route::get('/{booking}/download', [InvoiceController::class, 'download'])
        ->name('resepsionis.bookings.invoice.download');

    Route::get('/{booking}/print', [InvoiceController::class, 'print'])
        ->name('resepsionis.bookings.invoice.print');

    Route::get('/{booking}/stream', [InvoiceController::class, 'stream'])
        ->name('resepsionis.bookings.invoice.stream');
});
