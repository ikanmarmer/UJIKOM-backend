<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        // Contoh: jika role adalah "admin"
        if ($user->role === 'admin') {
            return redirect()->intended('/admin');  // atau route panel admin
        }

        // Contoh: jika role adalah "owner"
        if ($user->role === 'owner') {
            return redirect()->intended('/owner');  // atau route panel owner
        }

        // Contoh: jika role adalah "manager"
        if ($user->role === 'manager') {
            return redirect()->intended('/manager');  // atau route panel manager
        }

        // Contoh: jika role adalah "resepsionis"
        if ($user->role === 'resepsionis') {
            return redirect()->intended('/resepsionis');  // atau route panel resepsionis
        }

        // fallback ke default panel (Filament)
        return redirect()->intended(filament()->getUrl());
    }
}
