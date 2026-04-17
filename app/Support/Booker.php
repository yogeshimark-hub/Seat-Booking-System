<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Booker
{
    /**
     * Returns the currently logged-in booker from either guard.
     * Shape: ['id' => int, 'type' => 'user'|'admin'] or null if nobody is logged in.
     */
    public static function current(): ?array
    {
        if (Auth::guard('web')->check()) {
            return ['id' => Auth::guard('web')->id(), 'type' => 'user'];
        }

        if (Auth::guard('admin')->check()) {
            return ['id' => Auth::guard('admin')->id(), 'type' => 'admin'];
        }

        return null;
    }
}
