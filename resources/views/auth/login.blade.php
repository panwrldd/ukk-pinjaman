<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk ke GearHub — Sistem Peminjaman Peralatan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-900">
    <div class="flex min-h-full">
        <!-- Left Institutional Panel -->
        <div class="hidden w-1/2 flex-col justify-between bg-slate-900 p-12 text-white lg:flex">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-700 text-white">
                        <x-icon name="layers" class="h-5 w-5" />
                    </div>
                    <div>
                        <span class="block text-lg font-bold tracking-tight">GearHub</span>
                        <span class="block text-xs text-slate-400">Inventory & Laboratory Loan Management</span>
                    </div>
                </div>

                <div class="mt-14 max-w-md">
                    <h1 class="text-2xl font-bold leading-snug text-white">
                        Tata Kelola Inventaris Multimedia & Laboratorium Secara Terukur
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        Platform terpusat untuk pencatatan ketersediaan alat, pengajuan peminjaman berjenjang, pemeriksaan kondisi fisik pengembalian, perhitungan denda otomatis, serta pelaporan audit laboratorium.
                    </p>
                </div>

                <div class="mt-10 space-y-5 max-w-md">
                    <div class="flex items-start gap-3.5">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                            <x-icon name="package" class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Akurasi Stok Real-Time</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">Pemisahan otomatis antara stok tersedia, sedang dipinjam, dan unit rusak berat dalam perbaikan.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                            <x-icon name="shield-check" class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Verifikasi & Transaksi Database</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">Didukung Stored Procedure, Function, Trigger, dan transaksi ACID untuk menjaga integritas data.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                            <x-icon name="file-text" class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Pelaporan Resmi Siap Cetak</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">Ekspor laporan inventaris, peminjaman, keterlambatan, dan denda ke format cetak PDF dan Excel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-6 text-xs text-slate-400">
                Unit Pelaksana Teknis Laboratorium RPL & Multimedia
            </div>
        </div>

        <!-- Right Login Form Panel -->
        <div class="flex flex-1 flex-col justify-center bg-white px-6 py-12 sm:px-12 lg:px-16">
            <div class="mx-auto w-full max-w-sm" x-data="{ showPassword: false, submitting: false, identity: @js(old('identity', '')), password: '' }">
                <!-- Mobile Brand -->
                <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white">
                        <x-icon name="layers" class="h-5 w-5" />
                    </div>
                    <div>
                        <span class="block text-base font-bold text-slate-900">GearHub</span>
                        <span class="block text-xs text-slate-500">Sistem Peminjaman Alat Lab</span>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Masuk ke Akun Anda</h2>
                    <p class="mt-1.5 text-xs text-slate-500">Gunakan username atau email resmi yang terdaftar di sistem laboratorium.</p>
                </div>

                @if(session('success'))
                    <div class="mt-5 flex items-start gap-2.5 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                        <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mt-5 flex items-start gap-2.5 rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                        <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="mt-6 space-y-4" @submit="submitting = true">
                    @csrf

                    <div>
                        <label for="identity" class="input-label">Username atau Email</label>
                        <input
                            id="identity"
                            name="identity"
                            type="text"
                            x-model="identity"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Contoh: admin atau budi.santoso@siswa..."
                            class="input-field @error('identity') input-error @enderror"
                        >
                    </div>

                    <div>
                        <label for="password" class="input-label">Kata Sandi</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                x-model="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                class="input-field pr-10 @error('password') input-error @enderror"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                tabindex="-1"
                            >
                                <x-icon name="eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span>Ingat sesi pada perangkat ini</span>
                        </label>
                    </div>

                    <button type="submit" :disabled="submitting" class="btn-primary w-full py-2.5 text-sm">
                        <x-icon name="log-in" class="h-4 w-4" />
                        <span x-text="submitting ? 'Memverifikasi Kredensial...' : 'Masuk ke Sistem'"></span>
                    </button>
                </form>

                <!-- Quick Credential Selector for UKK Demo & Evaluation -->
                <div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-3.5" x-data="{ openDemo: true }">
                    <button type="button" @click="openDemo = !openDemo" class="flex w-full items-center justify-between text-left text-xs font-semibold text-slate-700">
                        <span class="flex items-center gap-1.5">
                            <x-icon name="info" class="h-3.5 w-3.5 text-blue-700" />
                            <span>Akun Demo Pengujian Role (UKK)</span>
                        </span>
                        <span class="text-[11px] font-normal text-slate-500" x-text="openDemo ? 'Sembunyikan' : 'Tampilkan'"></span>
                    </button>
                    <div x-show="openDemo" class="mt-3 grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="identity = 'admin'; password = 'password123'"
                            class="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40"
                        >
                            <span class="block text-xs font-bold text-slate-900">Admin</span>
                            <span class="block font-mono text-[10px] text-slate-500">admin</span>
                        </button>
                        <button
                            type="button"
                            @click="identity = 'petugas'; password = 'password123'"
                            class="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40"
                        >
                            <span class="block text-xs font-bold text-slate-900">Petugas</span>
                            <span class="block font-mono text-[10px] text-slate-500">petugas</span>
                        </button>
                        <button
                            type="button"
                            @click="identity = 'peminjam'; password = 'password123'"
                            class="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40"
                        >
                            <span class="block text-xs font-bold text-slate-900">Peminjam</span>
                            <span class="block font-mono text-[10px] text-slate-500">peminjam</span>
                        </button>
                    </div>
                    <p x-show="openDemo" class="mt-2 text-[11px] text-slate-500">Klik salah satu role di atas untuk mengisi otomatis kredensial uji (<code class="font-mono">password123</code>).</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
