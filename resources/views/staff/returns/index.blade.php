@extends('layouts.app')

@section('title', 'Pemantauan & Verifikasi Pengembalian')
@section('header', 'Pemantauan & Verifikasi Pengembalian Alat')
@section('subheader', 'Periksa fisik peralatan yang dikembalikan, tentukan kondisinya, dan konfirmasi denda keterlambatan.')

@section('content')
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Menunggu Verifikasi</span>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ $counts['pending'] }} pengajuan</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">Sedang Dipinjam (Belum Kembali)</span>
            <p class="mt-1 text-xl font-bold text-indigo-800">{{ $counts['active_unreturned'] }} pinjaman</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Selesai Diverifikasi</span>
            <p class="mt-1 text-xl font-bold text-emerald-700">{{ $counts['verified'] }} pengembalian</p>
        </div>
    </div>

    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('staff.returns.index') }}" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[240px]">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode pengembalian, kode pinjaman, atau peminjam..." class="input-field pl-9">
            </div>
            <select name="status" class="input-field w-auto">
                <option value="">Semua Status</option>
                <option value="pending_verification" @selected($status === 'pending_verification')>Menunggu Verifikasi</option>
                <option value="verified" @selected($status === 'verified')>Terverifikasi</option>
            </select>
            <button type="submit" class="btn-primary">Filter</button>
            @if($search !== '' || $status !== '')
                <a href="{{ route('staff.returns.index') }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="panel">
        @if($returns->isEmpty())
            <x-empty-state icon="rotate-ccw" title="Belum Ada Data Pengembalian" description="Pengajuan pengembalian dari peminjam akan muncul pada daftar ini." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Kode Pengembalian</th>
                            <th>Peminjam & Kode Pinjam</th>
                            <th>Jatuh Tempo & Kembali</th>
                            <th>Keterlambatan & Denda</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returns as $ret)
                            <tr>
                                <td>
                                    <a href="{{ route('staff.returns.show', $ret) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $ret->code }}</a>
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
                                        <span class="font-semibold text-rose-700">{{ $ret->late_days }} hari · {{ $ret->formatted_fine }}</span>
                                        <p class="text-[11px] {{ $ret->fine_paid ? 'text-emerald-700 font-semibold' : 'text-rose-600' }}">{{ $ret->fine_paid ? 'Lunas' : 'Belum Dibayar' }}</p>
                                    @else
                                        <span class="font-medium text-emerald-700">Tepat Waktu (Rp0)</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$ret->status_badge" :label="$ret->status_label" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('staff.returns.show', $ret) }}" class="{{ $ret->status === 'pending_verification' ? 'btn-primary' : 'btn-secondary' }} btn-xs">
                                        <span>{{ $ret->status === 'pending_verification' ? 'Verifikasi Kondisi' : 'Detail' }}</span>
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
