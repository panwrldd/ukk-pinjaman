@extends('layouts.app')

@section('title', $equipment->name)
@section('header', 'Detail Spesifikasi & Ketersediaan Alat')
@section('subheader', 'Periksa informasi teknis dan aturan penggunaan sebelum mengajukan peminjaman.')

@section('content')
    @php
        $canBorrow = $equipment->status === 'tersedia' && $equipment->qty_available > 0 && $equipment->condition !== 'rusak_berat';
    @endphp

    <div class="mb-4">
        <a href="{{ route('borrower.catalog.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Katalog Peralatan</span>
        </a>
    </div>

    <div class="panel overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="flex min-h-[260px] items-center justify-center border-b border-slate-200 bg-slate-50 p-6 lg:col-span-5 lg:border-b-0 lg:border-r">
                @if($equipment->photo_url)
                    <img src="{{ $equipment->photo_url }}" alt="{{ $equipment->name }}" class="max-h-72 rounded-lg object-contain">
                @else
                    <div class="text-center text-slate-400">
                        <x-icon name="camera" class="mx-auto h-14 w-14 opacity-50" />
                        <p class="mt-2 text-xs">Foto inventaris belum diunggah</p>
                    </div>
                @endif
            </div>

            <div class="p-6 lg:col-span-7">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold text-blue-800">{{ $equipment->code }}</span>
                    <span class="text-slate-300">·</span>
                    <span class="text-xs font-medium text-slate-600">{{ $equipment->category->name ?? '-' }}</span>
                </div>

                <h2 class="mt-1.5 text-xl font-bold text-slate-900">{{ $equipment->name }}</h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-status-badge :variant="$equipment->status" :label="$equipment->status_label" />
                    <x-status-badge :variant="$equipment->condition" :label="'Kondisi Fisik: ' . $equipment->condition_label" />
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-center">
                    <div>
                        <span class="block text-[11px] text-slate-500">Total Unit Lab</span>
                        <span class="text-lg font-bold text-slate-900">{{ $equipment->qty_total }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-semibold text-emerald-700">Siap Dipinjam</span>
                        <span class="text-lg font-bold text-emerald-700">{{ $equipment->qty_available }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-slate-500">Sedang Dipinjam</span>
                        <span class="text-lg font-bold text-slate-700">{{ $equipment->qty_borrowed }}</span>
                    </div>
                </div>

                <div class="mt-5 space-y-3 text-xs">
                    <div>
                        <h3 class="font-semibold text-slate-900">Deskripsi & Kelengkapan</h3>
                        <p class="mt-1 leading-relaxed text-slate-600">{{ $equipment->description ?: 'Tidak ada deskripsi tambahan.' }}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900">Lokasi Pengambilan</h3>
                        <p class="mt-1 text-slate-600">{{ $equipment->location ?: 'Loket Inventaris Laboratorium' }}</p>
                    </div>
                    @if($equipment->notes)
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-900">
                            <strong>Catatan SOP Penggunaan:</strong> {{ $equipment->notes }}
                        </div>
                    @endif
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5">
                    @if($canBorrow)
                        <a href="{{ route('borrower.loans.create', ['equipment_id' => $equipment->id]) }}" class="btn-primary">
                            <x-icon name="plus" class="h-4 w-4" />
                            <span>Ajukan Peminjaman Alat Ini</span>
                        </a>
                    @else
                        <button type="button" disabled class="btn-secondary">Stok Sedang Tidak Tersedia untuk Dipinjam</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($relatedEquipments->isNotEmpty())
        <div class="mt-8">
            <h3 class="mb-3 text-sm font-bold text-slate-900">Peralatan Lain pada Kategori Sama</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($relatedEquipments as $rel)
                    <a href="{{ route('borrower.catalog.show', $rel) }}" class="panel p-4 transition hover:border-blue-600">
                        <span class="font-mono text-[11px] text-slate-500">{{ $rel->code }}</span>
                        <p class="mt-1 font-semibold text-slate-900 line-clamp-1">{{ $rel->name }}</p>
                        <p class="mt-1 text-xs font-semibold text-emerald-700">{{ $rel->qty_available }} unit tersedia</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
