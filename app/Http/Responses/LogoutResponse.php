<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('home');  // atau route publik lain
    }
}
