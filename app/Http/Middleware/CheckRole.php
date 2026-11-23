<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Enforce backend Role-Based Access Control (RBAC).
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,petugas')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['identity' => 'Akun Anda tidak aktif. Silakan hubungi Administrator.']);
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses pada modul ini.');
        }

        return $next($request);
    }
}
