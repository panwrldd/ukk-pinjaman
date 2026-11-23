@extends('layouts.app')

@php
    $isEdit = $user->exists;
@endphp

@section('title', $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru')
@section('header', $isEdit ? 'Perbarui Data Pengguna' : 'Tambah Pengguna Baru')
@section('subheader', 'Lengkapi identitas akun dan tentukan hak akses role pengguna secara tepat.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                <span>Kembali ke Daftar Pengguna</span>
            </a>
        </div>

        <div class="panel" x-data="{ submitting: false }">
            <div class="panel-header">
                <h2 class="panel-title">{{ $isEdit ? 'Formulir Perubahan Pengguna: ' . $user->name : 'Formulir Registrasi Pengguna Sistem' }}</h2>
            </div>

            <form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="p-6 space-y-5" @submit="submitting = true">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="input-label">Nama Lengkap <span class="text-rose-600">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="input-field @error('name') input-error @enderror" placeholder="Nama lengkap beserta gelar bila ada">
                        @error('name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="username" class="input-label">Username Login <span class="text-rose-600">*</span></label>
                        <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required class="input-field font-mono @error('username') input-error @enderror" placeholder="contoh: budi.rpl">
                        @error('username') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="input-label">Alamat Email <span class="text-rose-600">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="input-field @error('email') input-error @enderror" placeholder="nama@sekolah.sch.id">
                        @error('email') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="identity_number" class="input-label">Nomor Identitas (NIS / NIP)</label>
                        <input type="text" id="identity_number" name="identity_number" value="{{ old('identity_number', $user->identity_number) }}" class="input-field font-mono @error('identity_number') input-error @enderror" placeholder="Opsional — NIS siswa atau NIP pegawai">
                        @error('identity_number') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="input-label">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input-field @error('phone') input-error @enderror" placeholder="0812xxxxxxxx">
                        @error('phone') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="role" class="input-label">Role Akses <span class="text-rose-600">*</span></label>
                        <select id="role" name="role" required class="input-field @error('role') input-error @enderror">
                            <option value="peminjam" @selected(old('role', $user->role ?: 'peminjam') === 'peminjam')>Peminjam (Siswa / Guru)</option>
                            <option value="petugas" @selected(old('role', $user->role) === 'petugas')>Petugas Laboratorium</option>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator</option>
                        </select>
                        @error('role') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true)) class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span class="text-xs font-semibold text-slate-800">Akun Aktif (Dapat Login ke Sistem)</span>
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="address" class="input-label">Kelas / Unit Kerja / Alamat</label>
                        <textarea id="address" name="address" rows="2" class="input-field @error('address') input-error @enderror" placeholder="Contoh: Kelas XII RPL 1 atau Ruang Teknisi Lab Multimedia">{{ old('address', $user->address) }}</textarea>
                        @error('address') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2 border-t border-slate-200 pt-4">
                        <p class="mb-3 text-xs font-semibold text-slate-800">
                            {{ $isEdit ? 'Ubah Kata Sandi (Kosongkan jika tidak ingin mengubah kata sandi)' : 'Pengaturan Kata Sandi Awal' }}
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="input-label">Kata Sandi {!! $isEdit ? '' : '<span class="text-rose-600">*</span>' !!}</label>
                                <input type="password" id="password" name="password" {{ $isEdit ? '' : 'required' }} class="input-field @error('password') input-error @enderror" placeholder="Minimal 6 karakter">
                                @error('password') <p class="error-msg">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="input-label">Konfirmasi Kata Sandi {!! $isEdit ? '' : '<span class="text-rose-600">*</span>' !!}</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" {{ $isEdit ? '' : 'required' }} class="input-field" placeholder="Ketik ulang kata sandi">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" :disabled="submitting" class="btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        <span x-text="submitting ? 'Menyimpan...' : '{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
