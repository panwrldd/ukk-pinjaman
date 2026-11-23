<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $identity = trim((string) $request->validated('identity'));
        $password = (string) $request->validated('password');
        $throttleKey = Str::lower($identity) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['identity' => "Terlalu banyak percobaan login gagal. Coba lagi dalam {$seconds} detik."])
                ->onlyInput('identity');
        }

        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $identity)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['identity' => 'Username/email atau kata sandi yang Anda masukkan salah.'])
                ->onlyInput('identity');
        }

        if (!$user->is_active) {
            return back()
                ->withErrors(['identity' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator laboratorium.'])
                ->onlyInput('identity');
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        ActivityLogService::log(
            action: 'login',
            description: "Pengguna {$user->name} ({$user->username} / {$user->role}) berhasil login.",
            module: 'auth',
            referenceType: 'users',
            referenceId: $user->id,
            userId: $user->id
        );

        return $this->redirectByRole($user->role)
            ->with('success', "Selamat datang kembali, {$user->name}.");
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogService::log(
                action: 'logout',
                description: "Pengguna {$user->name} ({$user->username}) keluar dari sistem.",
                module: 'auth',
                referenceType: 'users',
                referenceId: $user->id,
                userId: $user->id
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Sesi Anda telah diakhiri dengan aman.');
    }

    public function redirectAuthenticated(): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return $this->redirectByRole(Auth::user()->role);
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'petugas' => redirect()->route('staff.dashboard'),
            'peminjam' => redirect()->route('borrower.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
