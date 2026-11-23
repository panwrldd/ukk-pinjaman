@extends('layouts.app')

@section('title', 'Inventaris Peralatan')
@section('header', 'Manajemen Inventaris Alat')
@section('subheader', 'Kelola data peralatan multimedia & laboratorium, posisi stok, lokasi penyimpanan, dan kondisi fisik.')

@section('content')
    <!-- Inventory Summary Strip -->
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jenis Peralatan</span>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($summary['total_items']) }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Unit</span>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($summary['total_units']) }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Unit Tersedia</span>
            <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($summary['available_units']) }}</p>
        </div>
        <div class="panel px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Unit Rusak / Servis</span>
            <p class="mt-1 text-xl font-bold text-rose-700">{{ number_format($summary['damaged_units']) }}</p>
        </div>
    </div>

    <!-- Search, Filter & Add Toolbar -->
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('admin.equipments.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="relative lg:col-span-4">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode, nama alat, atau lokasi..." class="input-field pl-9">
            </div>

            <div class="lg:col-span-2">
                <select name="category_id" class="input-field">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <select name="condition" class="input-field">
                    <option value="">Semua Kondisi</option>
                    <option value="baik" @selected($condition === 'baik')>Baik</option>
                    <option value="rusak_ringan" @selected($condition === 'rusak_ringan')>Rusak Ringan</option>
                    <option value="rusak_berat" @selected($condition === 'rusak_berat')>Rusak Berat</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <select name="status" class="input-field">
                    <option value="">Semua Status</option>
                    <option value="tersedia" @selected($status === 'tersedia')>Tersedia</option>
                    <option value="tidak_tersedia" @selected($status === 'tidak_tersedia')>Tidak Tersedia</option>
                    <option value="maintenance" @selected($status === 'maintenance')>Maintenance</option>
                </select>
            </div>

            <div class="flex items-center gap-2 lg:col-span-2">
                <button type="submit" class="btn-secondary flex-1">Filter</button>
                @if($search !== '' || $categoryId !== '' || $condition !== '' || $status !== '')
                    <a href="{{ route('admin.equipments.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-xs text-slate-500">Menampilkan {{ $equipments->firstItem() ?? 0 }}–{{ $equipments->lastItem() ?? 0 }} dari {{ $equipments->total() }} jenis peralatan</p>
        <a href="{{ route('admin.equipments.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            <span>Tambah Alat Baru</span>
        </a>
    </div>

    <!-- Equipment Table -->
    <div class="panel">
        @if($equipments->isEmpty())
            <x-empty-state icon="package" title="Data Peralatan Kosong" description="Belum ada peralatan yang sesuai dengan kriteria pencarian Anda.">
                <a href="{{ route('admin.equipments.create') }}" class="btn-primary">Tambah Peralatan</a>
            </x-empty-state>
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Peralatan</th>
                            <th>Kategori & Lokasi</th>
                            <th>Kondisi</th>
                            <th>Stok (Tersedia / Total)</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipments as $eq)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($eq->photo_url)
                                            <img src="{{ $eq->photo_url }}" alt="{{ $eq->name }}" class="h-10 w-10 shrink-0 rounded-md border border-slate-200 object-cover">
                                        @else
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-400">
                                                <x-icon name="camera" class="h-4 w-4" />
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.equipments.show', $eq) }}" class="block font-semibold text-slate-900 hover:text-blue-800">{{ $eq->name }}</a>
                                            <span class="font-mono text-xs text-slate-500">{{ $eq->code }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-medium text-slate-800">{{ $eq->category->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $eq->location ?: 'Lokasi belum diatur' }}</p>
                                </td>
                                <td>
                                    <x-status-badge :variant="$eq->condition" :label="$eq->condition_label" />
                                </td>
                                <td>
                                    <div class="text-xs">
                                        <span class="font-bold {{ $eq->qty_available > 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $eq->qty_available }}</span>
                                        <span class="text-slate-400">/</span>
                                        <span class="font-semibold text-slate-800">{{ $eq->qty_total }} unit</span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2 text-[11px] text-slate-500">
                                        @if($eq->qty_borrowed > 0)
                                            <span class="text-blue-700">Dipinjam: {{ $eq->qty_borrowed }}</span>
                                        @endif
                                        @if($eq->qty_damaged > 0)
                                            <span class="text-rose-600">Rusak: {{ $eq->qty_damaged }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <x-status-badge :variant="$eq->status" :label="$eq->status_label" />
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.equipments.show', $eq) }}" class="btn-secondary btn-xs" title="Detail">
                                            <x-icon name="eye" class="h-3.5 w-3.5" />
                                        </a>
                                        <a href="{{ route('admin.equipments.edit', $eq) }}" class="btn-secondary btn-xs" title="Edit">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.equipments.destroy', $eq) }}" onsubmit="return confirm('Hapus peralatan {{ addslashes($eq->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger-outline btn-xs" title="Hapus">
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($equipments->hasPages())
                <div class="border-t border-slate-200 px-5 py-3.5">
                    {{ $equipments->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
