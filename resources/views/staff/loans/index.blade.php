@extends('layouts.app')

@section('title', 'Persetujuan & Penyerahan Peminjaman')
@section('header', 'Daftar Pengajuan & Peminjaman Alat')
@section('subheader', 'Periksa pengajuan peminjaman, setujui atau tolak dengan alasan jelas, dan catat penyerahan alat.')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @php
                $tabs = [
                    '' => 'Semua (' . $tabCounts['all'] . ')',
                    'pending' => 'Menunggu (' . $tabCounts['pending'] . ')',
                    'approved' => 'Siap Diserahkan (' . $tabCounts['approved'] . ')',
                    'borrowed' => 'Sedang Dipinjam (' . $tabCounts['borrowed'] . ')',
                    'overdue' => 'Terlambat (' . $tabCounts['overdue'] . ')',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('staff.loans.index', array_filter(['status' => $key, 'search' => $search])) }}"
                   class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $status === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('staff.loans.index') }}" class="flex flex-wrap items-center gap-2.5">
            @if($status !== '')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative flex-1 min-w-[240px]">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode pengajuan, nama siswa/guru, atau keperluan..." class="input-field pl-9">
            </div>
            <button type="submit" class="btn-primary">Cari</button>
            @if($search !== '' || $status !== '')
                <a href="{{ route('staff.loans.index') }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="panel">
        @if($loans->isEmpty())
            <x-empty-state icon="clipboard-check" title="Tidak Ada Data Pengajuan" description="Tidak ada peminjaman yang cocok dengan filter antrian saat ini." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Kode Pengajuan</th>
                            <th>Peminjam</th>
                            <th>Peralatan & Keperluan</th>
                            <th>Tanggal Pinjam & Kembali</th>
                            <th>Status</th>
                            <th class="text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loans as $loan)
                            <tr>
                                <td>
                                    <a href="{{ route('staff.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                    <p class="text-[11px] text-slate-500">{{ $loan->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $loan->user->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $loan->user->identity_number ?: '@' . $loan->user->username }}</p>
                                </td>
                                <td class="max-w-xs">
                                    <p class="truncate text-xs font-medium text-slate-800">
                                        {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ')')->implode(', ') }}
                                    </p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $loan->purpose }}</p>
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    <p>{{ $loan->loan_date?->format('d/m/Y') }} &rarr; <strong>{{ $loan->due_date?->format('d/m/Y') }}</strong></p>
                                    @if($loan->current_late_days > 0 && in_array($loan->status, ['borrowed', 'overdue']))
                                        <span class="font-semibold text-rose-600">Terlambat {{ $loan->current_late_days }} hari</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('staff.loans.show', $loan) }}" class="{{ in_array($loan->status, ['pending', 'approved']) ? 'btn-primary' : 'btn-secondary' }} btn-xs">
                                        <span>{{ $loan->status === 'pending' ? 'Periksa Pengajuan' : ($loan->status === 'approved' ? 'Serahkan Alat' : 'Detail') }}</span>
                                    </a>
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
