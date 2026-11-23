<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GearHub') — Sistem Manajemen Peminjaman Alat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false }">
    @php
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
    @endphp

    <x-toast />

    <!-- Mobile Backdrop -->
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="no-print fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
    ></div>

    <!-- Sidebar Navigation -->
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="no-print fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 ease-out"
    >
        <!-- Brand Header -->
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-800 px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-700 text-white">
                    <x-icon name="layers" class="h-4 w-4" />
                </div>
                <div>
                    <span class="block text-sm font-bold tracking-tight text-white">GearHub</span>
                    <span class="block text-[10px] font-medium uppercase tracking-wider text-slate-400">Lab & Multimedia</span>
                </div>
            </a>
            <button type="button" @click="sidebarOpen = false" class="rounded p-1 text-slate-400 hover:text-white lg:hidden">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <!-- Role Navigation Links -->
        <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-5">
            @if($authUser->isAdmin())
                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Ikhtisar</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="layout-dashboard" class="h-4 w-4 shrink-0" />
                            <span>Dashboard Admin</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Data Master</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.equipments.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.equipments.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="package" class="h-4 w-4 shrink-0" />
                            <span>Inventaris Alat</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.categories.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="tags" class="h-4 w-4 shrink-0" />
                            <span>Kategori Alat</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="users" class="h-4 w-4 shrink-0" />
                            <span>Manajemen Pengguna</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Transaksi & Tata Kelola</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.loans.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.loans.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="clipboard-list" class="h-4 w-4 shrink-0" />
                            <span>Data Peminjaman</span>
                        </a>
                        <a href="{{ route('admin.returns.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.returns.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="rotate-ccw" class="h-4 w-4 shrink-0" />
                            <span>Data Pengembalian</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('reports.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="file-text" class="h-4 w-4 shrink-0" />
                            <span>Pusat Laporan</span>
                        </a>
                        <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.activity-logs.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="activity" class="h-4 w-4 shrink-0" />
                            <span>Log Aktivitas</span>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('admin.settings.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="settings" class="h-4 w-4 shrink-0" />
                            <span>Pengaturan & Denda</span>
                        </a>
                    </div>
                </div>
            @elseif($authUser->isPetugas())
                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Meja Operasional</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('staff.dashboard') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="layout-dashboard" class="h-4 w-4 shrink-0" />
                            <span>Dashboard Petugas</span>
                        </a>
                        <a href="{{ route('staff.loans.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('staff.loans.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="clipboard-check" class="h-4 w-4 shrink-0" />
                            <span>Persetujuan & Penyerahan</span>
                        </a>
                        <a href="{{ route('staff.returns.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('staff.returns.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="rotate-ccw" class="h-4 w-4 shrink-0" />
                            <span>Verifikasi Pengembalian</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Inventaris & Laporan</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('staff.equipments.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('staff.equipments.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="package" class="h-4 w-4 shrink-0" />
                            <span>Kondisi & Stok Alat</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('reports.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="file-text" class="h-4 w-4 shrink-0" />
                            <span>Laporan & Cetak</span>
                        </a>
                    </div>
                </div>
            @else
                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Menu Peminjam</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('borrower.dashboard') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('borrower.dashboard') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="layout-dashboard" class="h-4 w-4 shrink-0" />
                            <span>Ringkasan Saya</span>
                        </a>
                        <a href="{{ route('borrower.catalog.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('borrower.catalog.*') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="package" class="h-4 w-4 shrink-0" />
                            <span>Katalog Peralatan</span>
                        </a>
                        <a href="{{ route('borrower.loans.create') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('borrower.loans.create') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="plus" class="h-4 w-4 shrink-0" />
                            <span>Ajukan Peminjaman</span>
                        </a>
                        <a href="{{ route('borrower.loans.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition {{ request()->routeIs('borrower.loans.index', 'borrower.loans.show') ? 'bg-blue-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <x-icon name="clipboard-list" class="h-4 w-4 shrink-0" />
                            <span>Pinjaman & Riwayat</span>
                        </a>
                    </div>
                </div>
            @endif
        </nav>

        <!-- Sidebar User Footer -->
        <div class="border-t border-slate-800 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white ring-1 ring-slate-700">
                    {{ $authUser->initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-white">{{ $authUser->name }}</p>
                    <p class="truncate text-[11px] text-slate-400">{{ $authUser->role_label }} · {{ '@' . $authUser->username }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md border border-slate-700 bg-slate-800/80 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-rose-600 hover:border-rose-600 hover:text-white">
                    <x-icon name="log-out" class="h-3.5 w-3.5" />
                    <span>Keluar Sistem</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Column -->
    <div class="flex min-h-screen flex-col lg:pl-64">
        <!-- Top Header -->
        <header class="no-print sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = true" class="rounded-md border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
                    <x-icon name="menu" class="h-4 w-4" />
                </button>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 sm:text-base">@yield('header', 'GearHub')</h1>
                    @hasSection('subheader')
                        <p class="hidden text-xs text-slate-500 sm:block">@yield('subheader')</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-600 md:flex">
                    <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                    <span>{{ now()->translatedFormat('d F Y') }}</span>
                </div>
                <x-status-badge :variant="$authUser->role" :label="$authUser->role_label" />
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        <!-- Subtle Institutional Footer -->
        <footer class="no-print border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>&copy; {{ date('Y') }} GearHub — Sistem Manajemen Peminjaman Peralatan Multimedia & Laboratorium</span>
                <span class="font-mono text-[11px] text-slate-400">UKK RPL · Paket 1</span>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
