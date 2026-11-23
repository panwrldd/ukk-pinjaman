@extends('layouts.app')

@section('title', 'Pusat Laporan & Cetak')
@section('header', 'Pusat Laporan & Ekspor Dokumen')
@section('subheader', 'Filter data aktual database, tinjau pratinjau laporan, cetak langsung, atau ekspor ke format PDF dan Excel.')

@section('content')
    @php
        $exportParams = array_filter(array_merge(['type' => $type], $filters), fn($v) => $v !== '');
    @endphp

    <!-- Report Type Selector Tabs -->
    <div class="mb-5 flex flex-wrap items-center gap-1.5">
        @foreach($availableReports as $key => $meta)
            <a href="{{ route('reports.index', ['type' => $key]) }}"
               class="rounded-md px-3 py-2 text-xs font-semibold transition {{ $type === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ str_replace('Laporan ', '', $meta['title']) }}
            </a>
        @endforeach
    </div>

    <!-- Contextual Filter & Export Toolbar -->
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <input type="hidden" name="type" value="{{ $type }}">

            @if(in_array($type, ['loans', 'returns', 'overdue', 'fines']))
                <div class="lg:col-span-2">
                    <label class="input-label">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-field">
                </div>
                <div class="lg:col-span-2">
                    <label class="input-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-field">
                </div>
            @endif

            @if(in_array($type, ['loans', 'returns', 'overdue', 'equipments']))
                <div class="lg:col-span-3">
                    <label class="input-label">Kategori Alat</label>
                    <select name="category_id" class="input-field">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $filters['category_id'] === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($type === 'loans')
                <div class="lg:col-span-3">
                    <label class="input-label">Status Peminjaman</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Menunggu Persetujuan</option>
                        <option value="approved" @selected($filters['status'] === 'approved')>Disetujui</option>
                        <option value="borrowed" @selected($filters['status'] === 'borrowed')>Sedang Dipinjam</option>
                        <option value="overdue" @selected($filters['status'] === 'overdue')>Terlambat (Overdue)</option>
                        <option value="returned" @selected($filters['status'] === 'returned')>Dikembalikan</option>
                        <option value="rejected" @selected($filters['status'] === 'rejected')>Ditolak</option>
                    </select>
                </div>
            @elseif($type === 'returns')
                <div class="lg:col-span-3">
                    <label class="input-label">Status Verifikasi</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="pending_verification" @selected($filters['status'] === 'pending_verification')>Menunggu Verifikasi</option>
                        <option value="verified" @selected($filters['status'] === 'verified')>Terverifikasi</option>
                    </select>
                </div>
            @elseif($type === 'fines')
                <div class="lg:col-span-3">
                    <label class="input-label">Status Pembayaran Denda</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="paid" @selected($filters['status'] === 'paid')>Lunas</option>
                        <option value="unpaid" @selected($filters['status'] === 'unpaid')>Belum Dibayar</option>
                    </select>
                </div>
            @elseif($type === 'equipments')
                <div class="lg:col-span-3">
                    <label class="input-label">Kondisi Alat</label>
                    <select name="condition" class="input-field">
                        <option value="">Semua Kondisi</option>
                        <option value="baik" @selected($filters['condition'] === 'baik')>Baik</option>
                        <option value="rusak_ringan" @selected($filters['condition'] === 'rusak_ringan')>Rusak Ringan</option>
                        <option value="rusak_berat" @selected($filters['condition'] === 'rusak_berat')>Rusak Berat</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="input-label">Status Ketersediaan</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="tersedia" @selected($filters['status'] === 'tersedia')>Tersedia</option>
                        <option value="tidak_tersedia" @selected($filters['status'] === 'tidak_tersedia')>Tidak Tersedia</option>
                        <option value="maintenance" @selected($filters['status'] === 'maintenance')>Maintenance</option>
                    </select>
                </div>
            @elseif($type === 'categories')
                <div class="lg:col-span-3">
                    <label class="input-label">Status Kategori</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            @elseif($type === 'users')
                <div class="lg:col-span-3">
                    <label class="input-label">Role Pengguna</label>
                    <select name="role" class="input-field">
                        <option value="">Semua Role</option>
                        <option value="admin" @selected($filters['role'] === 'admin')>Administrator</option>
                        <option value="petugas" @selected($filters['role'] === 'petugas')>Petugas Lab</option>
                        <option value="peminjam" @selected($filters['role'] === 'peminjam')>Peminjam</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="input-label">Status Akun</label>
                    <select name="status" class="input-field">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            @endif

            <div class="flex items-end gap-2 lg:col-span-2">
                <button type="submit" class="btn-primary flex-1">Terapkan</button>
                <a href="{{ route('reports.index', ['type' => $type]) }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($report['summary'] as $stat)
            <div class="panel px-4 py-3.5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $stat['label'] }}</span>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Preview Header & Export Actions -->
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">{{ $report['meta']['title'] }}</h2>
                <p class="text-xs text-slate-500">{{ $report['meta']['subtitle'] }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.print', $exportParams) }}" target="_blank" class="btn-secondary btn-xs">
                    <x-icon name="printer" class="h-3.5 w-3.5" />
                    <span>Cetak Laporan</span>
                </a>
                <a href="{{ route('reports.export.pdf', $exportParams) }}" class="btn-primary btn-xs">
                    <x-icon name="download" class="h-3.5 w-3.5" />
                    <span>Unduh PDF</span>
                </a>
                <a href="{{ route('reports.export.excel', $exportParams) }}" class="btn-emerald btn-xs">
                    <x-icon name="file-spreadsheet" class="h-3.5 w-3.5" />
                    <span>Export Excel (.xlsx)</span>
                </a>
            </div>
        </div>

        @if(empty($report['rows']))
            <x-empty-state icon="file-text" title="Tidak Ada Data Laporan" description="Tidak terdapat baris data untuk filter yang dipilih." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            @foreach($report['headings'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr>
                                @foreach($row as $colIdx => $cell)
                                    <td class="{{ $colIdx === 1 ? 'font-mono text-xs font-semibold text-slate-900' : 'text-xs' }}">
                                        {{ $cell }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
