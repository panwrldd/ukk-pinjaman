@extends('layouts.app')

@section('title', 'Kondisi & Ketersediaan Alat')
@section('header', 'Pemantauan Kondisi & Ketersediaan Alat')
@section('subheader', 'Daftar inventaris peralatan laboratorium beserta posisi stok tersedia, dipinjam, dan status kondisi fisik.')

@section('content')
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('staff.equipments.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="relative lg:col-span-4">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode, nama peralatan, atau lokasi..." class="input-field pl-9">
            </div>
            <div class="lg:col-span-3">
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
            <div class="lg:col-span-3 flex items-center gap-2">
                <select name="status" class="input-field flex-1">
                    <option value="">Semua Status</option>
                    <option value="tersedia" @selected($status === 'tersedia')>Tersedia</option>
                    <option value="tidak_tersedia" @selected($status === 'tidak_tersedia')>Tidak Tersedia</option>
                    <option value="maintenance" @selected($status === 'maintenance')>Maintenance</option>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <div class="panel">
        @if($equipments->isEmpty())
            <x-empty-state icon="package" title="Peralatan Tidak Ditemukan" description="Tidak ada peralatan inventaris yang sesuai dengan filter." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Kode & Nama Alat</th>
                            <th>Kategori</th>
                            <th>Lokasi Penyimpanan</th>
                            <th>Kondisi Fisik</th>
                            <th>Tersedia / Total</th>
                            <th>Dipinjam / Rusak</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipments as $eq)
                            <tr>
                                <td>
                                    <span class="font-mono text-xs font-bold text-slate-500">{{ $eq->code }}</span>
                                    <p class="font-semibold text-slate-900">{{ $eq->name }}</p>
                                </td>
                                <td class="text-xs text-slate-700">{{ $eq->category->name ?? '-' }}</td>
                                <td class="text-xs text-slate-600">{{ $eq->location ?: '-' }}</td>
                                <td><x-status-badge :variant="$eq->condition" :label="$eq->condition_label" /></td>
                                <td class="text-xs font-semibold">
                                    <span class="{{ $eq->qty_available > 0 ? 'text-emerald-700 font-bold' : 'text-rose-700 font-bold' }}">{{ $eq->qty_available }}</span>
                                    / {{ $eq->qty_total }} unit
                                </td>
                                <td class="text-xs text-slate-600">
                                    Dipinjam: {{ $eq->qty_borrowed }} · Rusak: <span class="{{ $eq->qty_damaged > 0 ? 'text-rose-700 font-semibold' : '' }}">{{ $eq->qty_damaged }}</span>
                                </td>
                                <td><x-status-badge :variant="$eq->status" :label="$eq->status_label" /></td>
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
