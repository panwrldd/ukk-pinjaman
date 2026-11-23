@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('header', 'Manajemen Pengguna')
@section('subheader', 'Kelola akun Administrator, Petugas Laboratorium, dan Peminjam beserta hak akses dan status keaktifannya.')

@section('content')
    <!-- Role Filter Tabs & Add Button -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('admin.users.index', array_filter(['search' => $search, 'status' => $status])) }}"
               class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $role === '' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                Semua ({{ $roleSummary['all'] }})
            </a>
            <a href="{{ route('admin.users.index', array_filter(['role' => 'admin', 'search' => $search, 'status' => $status])) }}"
               class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $role === 'admin' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                Admin ({{ $roleSummary['admin'] }})
            </a>
            <a href="{{ route('admin.users.index', array_filter(['role' => 'petugas', 'search' => $search, 'status' => $status])) }}"
               class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $role === 'petugas' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                Petugas ({{ $roleSummary['petugas'] }})
            </a>
            <a href="{{ route('admin.users.index', array_filter(['role' => 'peminjam', 'search' => $search, 'status' => $status])) }}"
               class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $role === 'peminjam' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                Peminjam ({{ $roleSummary['peminjam'] }})
            </a>
        </div>

        <a href="{{ route('admin.users.create') }}" class="btn-primary">
            <x-icon name="user-plus" class="h-4 w-4" />
            <span>Tambah Pengguna</span>
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-12">
            @if($role !== '')
                <input type="hidden" name="role" value="{{ $role }}">
            @endif
            <div class="relative sm:col-span-6">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama, username, email, atau NIS/NIP..."
                    class="input-field pl-9"
                >
            </div>
            <div class="sm:col-span-3">
                <select name="status" class="input-field">
                    <option value="">Semua Status Akun</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="flex items-center gap-2 sm:col-span-3">
                <button type="submit" class="btn-primary flex-1">Terapkan</button>
                @if($search !== '' || $status !== '' || $role !== '')
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="panel">
        @if($users->isEmpty())
            <x-empty-state icon="users" title="Pengguna Tidak Ditemukan" description="Tidak ada akun pengguna yang cocok dengan filter pencarian Anda." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Username & Identitas</th>
                            <th>Kontak / Unit</th>
                            <th>Role</th>
                            <th>Status Akun</th>
                            <th>Transaksi</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                            {{ $u->initials }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $u->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-mono text-xs font-semibold text-slate-800">{{ '@' . $u->username }}</span>
                                    <p class="text-xs text-slate-500">ID: {{ $u->identity_number ?: '-' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs text-slate-700">{{ $u->phone ?: '-' }}</p>
                                    <p class="max-w-[180px] truncate text-xs text-slate-500">{{ $u->address ?: '-' }}</p>
                                </td>
                                <td>
                                    <x-status-badge :variant="$u->role" :label="$u->role_label" />
                                </td>
                                <td>
                                    <x-status-badge :variant="$u->is_active ? 'active' : 'slate'" :label="$u->is_active ? 'Aktif' : 'Nonaktif'" />
                                </td>
                                <td class="text-xs font-medium text-slate-600">
                                    {{ $u->loans_count }} pinjaman
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.users.edit', $u) }}" class="btn-secondary btn-xs" title="Edit Pengguna">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            <span>Edit</span>
                                        </a>

                                        @if($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.toggle-status', $u) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-secondary btn-xs" title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            @if($u->loans_count === 0)
                                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }} secara permanen?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger-outline btn-xs" title="Hapus Pengguna">
                                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="border-t border-slate-200 px-5 py-3.5">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
