@extends('layouts.app')

@section('title', 'Dashboard Administrator')
@section('header', 'Pusat Kendali Inventaris & Tata Kelola')
@section('subheader', 'Ringkasan kesehatan inventaris laboratorium, tren peminjaman, pengawasan keterlambatan, dan jejak audit sistem.')

@section('content')
    @php
        $totalUnits = max(1, $stats['total_units']);
        $borrowedUnits = max(0, $stats['total_units'] - $stats['available_units'] - $stats['damaged_units']);
        $pctAvailable = round(($stats['available_units'] / $totalUnits) * 100, 1);
        $pctBorrowed = round(($borrowedUnits / $totalUnits) * 100, 1);
        $pctDamaged = max(0, round(100 - $pctAvailable - $pctBorrowed, 1));
        $maxMonthCount = max(1, collect($monthlyTrend)->max('count'));
        $maxCategoryQty = max(1, $categoryStats->max('borrowed_qty'));
    @endphp

    <!-- Top Quick Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-5 py-3.5 shadow-2xs">
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600">
            <span class="inline-flex items-center gap-1.5 font-medium text-slate-900">
                <x-icon name="banknote" class="h-4 w-4 text-blue-800" />
                Tarif Denda Aktif: <strong class="font-semibold">Rp{{ number_format($stats['fine_rate_per_day'], 0, ',', '.') }}/hari</strong>
            </span>
            <span class="hidden text-slate-300 sm:inline">|</span>
            <span>Denda Lunas: <strong class="text-emerald-700">Rp{{ number_format($stats['total_fines_collected'], 0, ',', '.') }}</strong></span>
            @if($stats['total_fines_unpaid'] > 0)
                <span>Belum Dibayar: <strong class="text-rose-700">Rp{{ number_format($stats['total_fines_unpaid'], 0, ',', '.') }}</strong></span>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.equipments.create') }}" class="btn-primary btn-xs">
                <x-icon name="plus" class="h-3.5 w-3.5" />
                <span>Tambah Alat</span>
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn-secondary btn-xs">
                <x-icon name="user-plus" class="h-3.5 w-3.5" />
                <span>Tambah Pengguna</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn-secondary btn-xs">
                <x-icon name="printer" class="h-3.5 w-3.5" />
                <span>Cetak Laporan</span>
            </a>
        </div>
    </div>

    <!-- Operational Governance Metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Pengguna</span>
                <x-icon name="users" class="h-4 w-4 text-slate-400" />
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_users']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['active_borrowers'] }} peminjam aktif</p>
        </div>

        <div class="panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jenis Alat</span>
                <x-icon name="package" class="h-4 w-4 text-slate-400" />
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_equipments']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['available_units'] }}/{{ $stats['total_units'] }} unit tersedia</p>
        </div>

        <div class="panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Kategori</span>
                <x-icon name="tags" class="h-4 w-4 text-slate-400" />
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_categories']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Klasifikasi inventaris</p>
        </div>

        <div class="panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Pinjaman Aktif</span>
                <x-icon name="clipboard-list" class="h-4 w-4 text-indigo-600" />
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['active_loans']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $borrowedUnits }} unit sedang keluar</p>
        </div>

        <a href="{{ route('admin.loans.index', ['status' => 'pending']) }}" class="panel p-4 transition hover:border-amber-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Menunggu</span>
                <x-icon name="clock" class="h-4 w-4 text-amber-600" />
            </div>
            <p class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($stats['pending_loans']) }}</p>
            <p class="mt-1 text-xs text-amber-700/80">{{ $stats['pending_returns'] }} kembali perlu cek</p>
        </a>

        <a href="{{ route('admin.loans.index', ['status' => 'overdue']) }}" class="panel p-4 transition hover:border-rose-300 {{ $stats['overdue_loans'] > 0 ? 'border-rose-200 bg-rose-50/30' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Terlambat</span>
                <x-icon name="alert-triangle" class="h-4 w-4 text-rose-600" />
            </div>
            <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($stats['overdue_loans']) }}</p>
            <p class="mt-1 text-xs text-rose-600">{{ $stats['damaged_units'] }} unit maintenance</p>
        </a>
    </div>

    <!-- Inventory Composition Progress Bar -->
    <div class="panel mt-6 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Komposisi Posisi Fisik Unit Inventaris ({{ $stats['total_units'] }} Total Unit)</h2>
                <p class="text-xs text-slate-500">Proporsi unit siap pakai di ruang penyimpanan, sedang dipinjam, dan dalam perbaikan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-xs bg-emerald-600"></span> Tersedia ({{ $stats['available_units'] }})</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-xs bg-blue-700"></span> Dipinjam ({{ $borrowedUnits }})</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-xs bg-rose-600"></span> Rusak / Maintenance ({{ $stats['damaged_units'] }})</span>
            </div>
        </div>
        <div class="mt-3 flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="bg-emerald-600 transition-all" style="width: {{ $pctAvailable }}%"></div>
            <div class="bg-blue-700 transition-all" style="width: {{ $pctBorrowed }}%"></div>
            <div class="bg-rose-600 transition-all" style="width: {{ $pctDamaged }}%"></div>
        </div>
    </div>

    <!-- Database Charts Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- 6-Month Loan Volume Chart -->
        <div class="panel lg:col-span-7">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Grafik Tren Transaksi Peminjaman (6 Bulan Terakhir)</h2>
                    <p class="text-xs text-slate-500">Dihitung langsung dari tanggal peminjaman pada tabel <code class="font-mono">loans</code>.</p>
                </div>
            </div>
            <div class="p-5">
                <div class="grid h-52 grid-cols-6 items-end gap-3 border-b border-slate-200 pb-3 pt-6">
                    @foreach($monthlyTrend as $point)
                        @php
                            $barHeight = $point['count'] > 0 ? max(12, round(($point['count'] / $maxMonthCount) * 100)) : 4;
                        @endphp
                        <div class="flex h-full flex-col items-center justify-end gap-2">
                            <span class="text-xs font-bold text-slate-700">{{ $point['count'] }}</span>
                            <div class="flex w-full max-w-[44px] items-end rounded-t bg-slate-100 h-36">
                                <div
                                    class="w-full rounded-t transition-all {{ $point['count'] > 0 ? 'bg-slate-900 hover:bg-blue-800' : 'bg-slate-200' }}"
                                    style="height: {{ $barHeight }}%"
                                    title="{{ $point['label'] }}: {{ $point['count'] }} transaksi"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2.5 grid grid-cols-6 gap-3 text-center">
                    @foreach($monthlyTrend as $point)
                        <span class="text-[11px] font-medium text-slate-500">{{ $point['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Category Utilization Breakdown -->
        <div class="panel lg:col-span-5">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Frekuensi Unit Dipinjam per Kategori</h2>
                    <p class="text-xs text-slate-500">Akumulasi kuantitas unit pada <code class="font-mono">loan_details</code>.</p>
                </div>
            </div>
            <div class="space-y-4 p-5">
                @forelse($categoryStats as $cat)
                    @php
                        $pctCat = $cat->borrowed_qty > 0 ? round(($cat->borrowed_qty / $maxCategoryQty) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                            <span class="font-semibold text-slate-900">{{ (int) $cat->borrowed_qty }} unit</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-800" style="width: {{ $pctCat }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500">Belum ada data kategori.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bottom Governance & Audit Columns -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Overdue & Maintenance Alerts -->
        <div class="space-y-6 lg:col-span-6">
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Pinjaman Melewati Jatuh Tempo (Overdue)</h2>
                    <a href="{{ route('admin.loans.index', ['status' => 'overdue']) }}" class="text-xs font-semibold text-blue-800 hover:underline">Lihat Semua</a>
                </div>
                @if($overdueLoans->isEmpty())
                    <x-empty-state icon="check-circle" title="Tidak Ada Keterlambatan Aktif" description="Seluruh peminjaman aktif berada dalam batas jadwal pengembalian." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($overdueLoans as $loan)
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.loans.show', $loan) }}" class="font-mono text-xs font-bold text-slate-900 hover:text-blue-800">{{ $loan->code }}</a>
                                        <x-status-badge variant="overdue" :label="$loan->current_late_days . ' hari terlambat'" />
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-600">{{ $loan->user->name ?? '-' }} · Jatuh tempo {{ $loan->due_date->format('d/m/Y') }}</p>
                                </div>
                                <a href="{{ route('admin.loans.show', $loan) }}" class="btn-secondary btn-xs shrink-0">Periksa</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Unit Rusak Berat / Dalam Perbaikan</h2>
                    <a href="{{ route('admin.equipments.index') }}" class="text-xs font-semibold text-blue-800 hover:underline">Kelola Inventaris</a>
                </div>
                @if($maintenanceEquipments->isEmpty())
                    <x-empty-state icon="wrench" title="Tidak Ada Unit Rusak" description="Seluruh unit peralatan siap digunakan dan tidak ada antrian servis." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($maintenanceEquipments as $eq)
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-900">{{ $eq->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $eq->code }} · Tersedia: {{ $eq->qty_available }}/{{ $eq->qty_total }} unit</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-status-badge variant="rusak_berat" :label="$eq->qty_damaged . ' unit rusak'" />
                                    <a href="{{ route('admin.equipments.show', $eq) }}" class="btn-secondary btn-xs">Pulihkan</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity & DB Trigger Audit Log -->
        <div class="panel lg:col-span-6">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Aktivitas Terbaru & Audit Database</h2>
                    <p class="text-xs text-slate-500">Mencakup log aplikasi dan trigger otomatis MariaDB.</p>
                </div>
                <a href="{{ route('admin.activity-logs.index') }}" class="text-xs font-semibold text-blue-800 hover:underline">Log Lengkap</a>
            </div>
            @if($recentLogs->isEmpty())
                <x-empty-state icon="activity" title="Belum Ada Aktivitas" description="Aktivitas pengguna dan perubahan status transaksi akan tercatat di sini." />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentLogs as $log)
                        <div class="flex items-start gap-3 px-5 py-3.5">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $log->module === 'db_trigger' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                <x-icon :name="$log->module === 'db_trigger' ? 'database' : 'activity'" class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <span class="text-xs font-semibold text-slate-900">
                                        {{ $log->user->name ?? 'Sistem Database' }}
                                        @if($log->module === 'db_trigger')
                                            <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-indigo-700">SQL TRIGGER</span>
                                        @endif
                                    </span>
                                    <span class="text-[11px] text-slate-400">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{{ $log->description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
