@extends('layouts.app')

@section('title', 'Portal Peminjam')
@section('header', 'Portal Peminjaman Peralatan Saya')
@section('subheader', 'Pantau jadwal pengembalian aktif, status pengajuan, dan telusuri peralatan laboratorium yang siap dipinjam.')

@section('content')
    @if($summary['overdue_count'] > 0)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-rose-300 bg-rose-50 px-5 py-4 text-xs text-rose-900">
            <div class="flex items-start gap-3">
                <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
                <div>
                    <p class="font-bold">Perhatian: Anda Memiliki {{ $summary['overdue_count'] }} Peminjaman yang Melewati Batas Waktu!</p>
                    <p class="mt-0.5 text-rose-800">Segera ajukan pengembalian dan serahkan alat ke loket laboratorium. Tarif denda keterlambatan berlaku Rp{{ number_format($summary['fine_rate'], 0, ',', '.') }}/hari.</p>
                </div>
            </div>
            <a href="{{ route('borrower.loans.index', ['status' => 'overdue']) }}" class="btn-danger btn-xs">Lihat Pinjaman Terlambat</a>
        </div>
    @endif

    <!-- Personal Status Summary -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="panel p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">Sedang Saya Pinjam</span>
            <p class="mt-1.5 text-2xl font-bold text-slate-900">{{ $summary['active_borrowed'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Wajib dijaga kondisinya</p>
        </div>
        <div class="panel p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Disetujui (Siap Diambil)</span>
            <p class="mt-1.5 text-2xl font-bold text-slate-900">{{ $summary['approved_ready'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Ambil di loket petugas</p>
        </div>
        <div class="panel p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Menunggu Verifikasi</span>
            <p class="mt-1.5 text-2xl font-bold text-slate-900">{{ $summary['pending_requests'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Sedang diperiksa petugas</p>
        </div>
        <div class="panel p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Selesai Dikembalikan</span>
            <p class="mt-1.5 text-2xl font-bold text-slate-900">{{ $summary['completed_loans'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">Riwayat peminjaman tuntas</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Active Schedule & Return Deadlines -->
        <div class="panel lg:col-span-7">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Jadwal Pinjaman Aktif & Batas Pengembalian</h2>
                    <p class="text-xs text-slate-500">Pastikan pengembalian dilakukan sebelum atau tepat pada tanggal jatuh tempo.</p>
                </div>
                <a href="{{ route('borrower.loans.create') }}" class="btn-primary btn-xs">
                    <x-icon name="plus" class="h-3.5 w-3.5" />
                    <span>Pinjam Alat</span>
                </a>
            </div>

            @if($activeSchedule->isEmpty())
                <x-empty-state icon="calendar" title="Tidak Ada Pinjaman Aktif" description="Anda sedang tidak meminjam peralatan saat ini. Silakan telusuri katalog untuk mengajukan peminjaman.">
                    <a href="{{ route('borrower.catalog.index') }}" class="btn-primary btn-xs">Jelajahi Katalog Alat</a>
                </x-empty-state>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($activeSchedule as $loan)
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('borrower.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                    <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
                                </div>
                                <p class="mt-1 text-xs font-semibold text-slate-900">
                                    {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ' unit)')->implode(', ') }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Batas Pengembalian: <strong class="{{ $loan->isLate() ? 'text-rose-700' : 'text-slate-800' }}">{{ $loan->due_date?->translatedFormat('d F Y') }}</strong>
                                    @if($loan->isLate())
                                        <span class="text-rose-600 font-semibold">({{ $loan->current_late_days }} hari terlambat)</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if(in_array($loan->status, ['borrowed', 'overdue']) && !$loan->equipmentReturn)
                                    <a href="{{ route('borrower.loans.show', $loan) }}" class="btn-primary btn-xs">Ajukan Kembali</a>
                                @elseif($loan->equipmentReturn)
                                    <x-status-badge variant="amber" label="Menunggu Cek Petugas" />
                                @else
                                    <a href="{{ route('borrower.loans.show', $loan) }}" class="btn-secondary btn-xs">Detail</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent History -->
        <div class="panel lg:col-span-5">
            <div class="panel-header">
                <h2 class="panel-title">Riwayat Pengajuan Terakhir</h2>
                <a href="{{ route('borrower.loans.index') }}" class="text-xs font-semibold text-blue-800 hover:underline">Semua Riwayat</a>
            </div>
            @if($recentHistory->isEmpty())
                <x-empty-state icon="clipboard-list" title="Belum Ada Riwayat" description="Pengajuan peminjaman Anda akan tercatat di sini." />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentHistory as $hist)
                        <a href="{{ route('borrower.loans.show', $hist) }}" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50">
                            <div class="min-w-0">
                                <span class="font-mono text-xs font-bold text-slate-800">{{ $hist->code }}</span>
                                <p class="truncate text-xs text-slate-600">{{ $hist->purpose }}</p>
                            </div>
                            <x-status-badge :variant="$hist->status_badge" :label="$hist->status_label" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Available Equipment Showcase -->
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Peralatan Siap Dipinjam di Laboratorium</h2>
                <p class="text-xs text-slate-500">Stok tersedia diperbarui secara real-time berdasarkan transaksi aktif.</p>
            </div>
            <a href="{{ route('borrower.catalog.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-800 hover:underline">
                <span>Buka Katalog Lengkap</span>
                <x-icon name="arrow-right" class="h-3.5 w-3.5" />
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($featuredEquipments as $eq)
                <div class="panel flex flex-col justify-between p-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-mono text-[11px] font-bold text-slate-500">{{ $eq->code }}</span>
                            <x-status-badge variant="tersedia" :label="$eq->qty_available . ' unit tersedia'" />
                        </div>
                        <a href="{{ route('borrower.catalog.show', $eq) }}" class="mt-1.5 block text-sm font-bold text-slate-900 hover:text-blue-800">{{ $eq->name }}</a>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $eq->category->name ?? '-' }} · {{ $eq->location ?: 'Lab Terpadu' }}</p>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                        <a href="{{ route('borrower.catalog.show', $eq) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Lihat Spesifikasi</a>
                        <a href="{{ route('borrower.loans.create', ['equipment_id' => $eq->id]) }}" class="btn-primary btn-xs">Pinjam</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
