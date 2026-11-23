@extends('layouts.app')

@section('title', 'Manajemen Pengembalian')
@section('header', 'Manajemen Data Pengembalian & Denda')
@section('subheader', 'Pantau pengembalian alat, verifikasi pemeriksaan kondisi fisik, dan konfirmasi pelunasan denda.')

@section('content')
    <!-- Summary Strip -->
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Menunggu Verifikasi</span>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ number_format($summary['pending']) }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Selesai Diverifikasi</span>
            <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($summary['verified']) }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Denda Belum Lunas</span>
            <p class="mt-1 text-xl font-bold text-rose-700">Rp{{ number_format($summary['unpaid_fines'], 0, ',', '.') }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-600">Total Denda Lunas</span>
            <p class="mt-1 text-xl font-bold text-slate-900">Rp{{ number_format($summary['paid_fines'], 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Filter & Direct Return Recording -->
    <div class="panel mb-5 p-4" x-data="{ recordModal: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.returns.index') }}" class="flex flex-1 flex-wrap items-center gap-2.5">
                <div class="relative min-w-[240px] flex-1 max-w-md">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode kembali, kode pinjam, atau peminjam..." class="input-field pl-9">
                </div>
                <select name="status" class="input-field w-auto">
                    <option value="">Semua Status</option>
                    <option value="pending_verification" @selected($status === 'pending_verification')>Menunggu Verifikasi</option>
                    <option value="verified" @selected($status === 'verified')>Terverifikasi</option>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
                @if($search !== '' || $status !== '')
                    <a href="{{ route('admin.returns.index') }}" class="btn-secondary">Reset</a>
                @endif
            </form>

            @if($activeBorrowedLoans->isNotEmpty())
                <button type="button" @click="recordModal = true" class="btn-primary">
                    <x-icon name="rotate-ccw" class="h-4 w-4" />
                    <span>Catat Pengembalian Baru</span>
                </button>
            @endif
        </div>

        <!-- Modal Record Return from Active Loan -->
        <div x-cloak x-show="recordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="recordModal = false" class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Catat Pengembalian dari Pinjaman Aktif</h3>
                    <button type="button" @click="recordModal = false" class="rounded p-1 text-slate-400 hover:bg-slate-100">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.returns.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="input-label">Pilih Transaksi Peminjaman Aktif <span class="text-rose-600">*</span></label>
                        <select name="loan_id" required class="input-field">
                            <option value="">-- Pilih Peminjaman --</option>
                            @foreach($activeBorrowedLoans as $al)
                                <option value="{{ $al->id }}">{{ $al->code }} — {{ $al->user->name ?? '-' }} (Jatuh tempo: {{ $al->due_date->format('d/m/Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Tanggal Dikembalikan</label>
                        <input type="date" name="return_date" value="{{ now()->toDateString() }}" required class="input-field">
                    </div>
                    <div>
                        <label class="input-label">Catatan Pengembalian</label>
                        <textarea name="notes" rows="2" class="input-field" placeholder="Catatan saat serah terima di loket..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="recordModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Lanjut ke Pemeriksaan Fisik</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="panel">
        @if($returns->isEmpty())
            <x-empty-state icon="rotate-ccw" title="Belum Ada Data Pengembalian" description="Data pengajuan dan verifikasi pengembalian akan muncul di sini." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Kode Pengembalian</th>
                            <th>Kode Pinjam & Peminjam</th>
                            <th>Jatuh Tempo vs Kembali</th>
                            <th>Keterlambatan & Denda</th>
                            <th>Status Verifikasi</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returns as $ret)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.returns.show', $ret) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $ret->code }}</a>
                                    <p class="text-[11px] text-slate-500">Verifikator: {{ $ret->verifiedBy->name ?? 'Belum diperiksa' }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $ret->returnedBy->name ?? '-' }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $ret->loan->code ?? '-' }}</p>
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    <p class="text-slate-600">Tempo: {{ $ret->loan?->due_date?->format('d/m/Y') }}</p>
                                    <p class="font-semibold text-slate-900">Kembali: {{ $ret->return_date?->format('d/m/Y') }}</p>
                                </td>
                                <td class="text-xs">
                                    @if($ret->late_days > 0)
                                        <span class="font-semibold text-rose-700">{{ $ret->late_days }} hari ({{ $ret->formatted_fine }})</span>
                                        <p class="text-[11px] {{ $ret->fine_paid ? 'text-emerald-700 font-semibold' : 'text-rose-600' }}">
                                            {{ $ret->fine_paid ? 'Lunas' : 'Belum Dibayar' }}
                                        </p>
                                    @else
                                        <span class="text-emerald-700 font-medium">Tepat Waktu (Rp0)</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$ret->status_badge" :label="$ret->status_label" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.returns.show', $ret) }}" class="{{ $ret->status === 'pending_verification' ? 'btn-primary' : 'btn-secondary' }} btn-xs">
                                        <span>{{ $ret->status === 'pending_verification' ? 'Periksa & Verifikasi' : 'Detail' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="border-t border-slate-200 px-5 py-3.5">
                    {{ $returns->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
