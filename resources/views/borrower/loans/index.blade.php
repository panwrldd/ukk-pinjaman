@extends('layouts.app')

@section('title', 'Pinjaman & Riwayat Saya')
@section('header', 'Status Pengajuan & Riwayat Peminjaman Saya')
@section('subheader', 'Lacak status persetujuan, tenggat pengembalian, dan riwayat peminjaman peralatan Anda.')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @php
                $tabs = [
                    '' => 'Semua (' . $counts['all'] . ')',
                    'pending' => 'Menunggu (' . $counts['pending'] . ')',
                    'approved' => 'Siap Diambil (' . $counts['approved'] . ')',
                    'borrowed' => 'Sedang Dipinjam (' . $counts['borrowed'] . ')',
                    'returned' => 'Selesai (' . $counts['returned'] . ')',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('borrower.loans.index', array_filter(['status' => $key, 'search' => $search])) }}"
                   class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $status === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <a href="{{ route('borrower.loans.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            <span>Ajukan Peminjaman Baru</span>
        </a>
    </div>

    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('borrower.loans.index') }}" class="flex flex-wrap items-center gap-2.5">
            @if($status !== '')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative flex-1 min-w-[220px]">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode transaksi atau keperluan..." class="input-field pl-9">
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
            @if($search !== '' || $status !== '')
                <a href="{{ route('borrower.loans.index') }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="panel">
        @if($loans->isEmpty())
            <x-empty-state icon="clipboard-list" title="Belum Ada Transaksi Peminjaman" description="Anda belum memiliki pengajuan atau riwayat peminjaman pada kategori ini.">
                <a href="{{ route('borrower.catalog.index') }}" class="btn-primary btn-xs">Lihat Katalog Alat</a>
            </x-empty-state>
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Kode Pinjaman</th>
                            <th>Peralatan</th>
                            <th>Tgl Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loans as $loan)
                            <tr>
                                <td>
                                    <a href="{{ route('borrower.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                    <p class="text-[11px] text-slate-500">{{ $loan->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="max-w-xs">
                                    <p class="truncate text-xs font-semibold text-slate-900">
                                        {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ')')->implode(', ') }}
                                    </p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $loan->purpose }}</p>
                                </td>
                                <td class="text-xs text-slate-700">{{ $loan->loan_date?->format('d/m/Y') }}</td>
                                <td class="text-xs">
                                    <span class="font-semibold {{ $loan->isLate() ? 'text-rose-700' : 'text-slate-800' }}">{{ $loan->due_date?->format('d/m/Y') }}</span>
                                    @if($loan->isLate())
                                        <span class="block text-[11px] font-semibold text-rose-600">Terlambat {{ $loan->current_late_days }} hari</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('borrower.loans.show', $loan) }}" class="btn-secondary btn-xs">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="border-t border-slate-200 px-5 py-3.5">
                    {{ $loans->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
