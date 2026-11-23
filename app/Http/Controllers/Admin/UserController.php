<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $users = User::withCount('loans')
            ->search($search)
            ->when(filled($role), fn (Builder $q) => $q->where('role', $role))
            ->when($status === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roleSummary = [
            'all' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'petugas' => User::where('role', 'petugas')->count(),
            'peminjam' => User::where('role', 'peminjam')->count(),
        ];

        return view('admin.users.index', compact('users', 'roleSummary', 'search', 'role', 'status'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::beginTransaction();
        try {
            $user = User::create($validated);

            ActivityLogService::log(
                action: 'user_created',
                description: "Menambahkan pengguna baru: {$user->name} ({$user->username} - {$user->role}).",
                module: 'users',
                referenceType: 'users',
                referenceId: $user->id,
                newValues: $user->only(['name', 'username', 'email', 'role', 'is_active'])
            );

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Akun pengguna {$user->name} berhasil ditambahkan.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menyimpan pengguna: ' . $e->getMessage());
        }
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', false);

        if ($user->id === Auth::id() && !$isActive) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.');
        }

        if ($user->id === Auth::id() && $validated['role'] !== 'admin') {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak dapat mengubah role akun Administrator Anda sendiri.');
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $validated['is_active'] = $isActive;

        DB::beginTransaction();
        try {
            $oldValues = $user->only(['name', 'username', 'email', 'role', 'is_active']);
            $user->update($validated);

            ActivityLogService::log(
                action: 'user_updated',
                description: "Memperbarui data pengguna: {$user->name} ({$user->username}).",
                module: 'users',
                referenceType: 'users',
                referenceId: $user->id,
                oldValues: $oldValues,
                newValues: $user->only(['name', 'username', 'email', 'role', 'is_active'])
            );

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        ActivityLogService::log(
            action: 'user_status_toggled',
            description: sprintf('Mengubah status akun %s (%s) menjadi %s.', $user->name, $user->username, $newStatus ? 'Aktif' : 'Nonaktif'),
            module: 'users',
            referenceType: 'users',
            referenceId: $user->id
        );

        return back()->with('success', 'Status akun ' . $user->name . ' berhasil diubah menjadi ' . ($newStatus ? 'Aktif' : 'Nonaktif') . '.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($user->loans()->exists() || $user->returns()->exists()) {
            return back()->with(
                'error',
                'Pengguna tidak dapat dihapus karena sudah memiliki riwayat transaksi peminjaman. Gunakan fitur Nonaktifkan Akun.'
            );
        }

        DB::beginTransaction();
        try {
            $name = $user->name;
            $username = $user->username;
            $id = $user->id;

            $user->delete();

            ActivityLogService::log(
                action: 'user_deleted',
                description: "Menghapus pengguna: {$name} ({$username}).",
                module: 'users',
                referenceType: 'users',
                referenceId: $id
            );

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Pengguna {$name} berhasil dihapus.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }
}
