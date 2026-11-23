<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    /**
     * Immediately terminate session if an authenticated user's account was deactivated.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['identity' => 'Akun Anda telah dinonaktifkan oleh Administrator.']);
        }

        return $next($request);
    }
}
