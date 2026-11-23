@extends('layouts.app')

@section('title', 'Katalog Peralatan Laboratorium')
@section('header', 'Katalog Peralatan Multimedia & Laboratorium')
@section('subheader', 'Periksa ketersediaan stok secara langsung dan pilih peralatan untuk diajukan peminjamannya.')

@section('content')
    <!-- Search & Filter Bar -->
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('borrower.catalog.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="relative lg:col-span-5">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama peralatan, kode inventaris, atau spesifikasi..." class="input-field pl-9">
            </div>

            <div class="lg:col-span-3">
                <select name="category_id" class="input-field">
                    <option value="">Semua Kategori Aktif</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }} ({{ $cat->equipments_count }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center lg:col-span-2">
                <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                    <input type="checkbox" name="available_only" value="1" @checked($onlyAvailable) class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>Hanya Stok Tersedia</span>
                </label>
            </div>

            <div class="flex items-center gap-2 lg:col-span-2">
                <button type="submit" class="btn-primary flex-1">Cari</button>
                @if($search !== '' || $categoryId !== '' || $onlyAvailable)
                    <a href="{{ route('borrower.catalog.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Equipment Catalog Grid -->
    @if($equipments->isEmpty())
        <div class="panel">
            <x-empty-state icon="search" title="Peralatan Tidak Ditemukan" description="Coba ubah kata kunci pencarian atau pilih kategori peralatan lainnya." />
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($equipments as $eq)
                @php
                    $canBorrow = $eq->status === 'tersedia' && $eq->qty_available > 0 && $eq->condition !== 'rusak_berat';
                @endphp
                <div class="panel flex flex-col justify-between overflow-hidden transition hover:border-slate-300">
                    <div>
                        <div class="relative flex h-40 items-center justify-center border-b border-slate-100 bg-slate-50">
                            @if($eq->photo_url)
                                <img src="{{ $eq->photo_url }}" alt="{{ $eq->name }}" class="h-full w-full object-cover">
                            @else
                                <x-icon name="camera" class="h-10 w-10 text-slate-300" />
                            @endif
                            <div class="absolute right-3 top-3">
                                <x-status-badge :variant="$canBorrow ? 'tersedia' : 'slate'" :label="$canBorrow ? $eq->qty_available . ' Tersedia' : 'Stok Kosong'" />
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-mono font-semibold">{{ $eq->code }}</span>
                                <span>{{ $eq->category->name ?? '-' }}</span>
                            </div>
                            <a href="{{ route('borrower.catalog.show', $eq) }}" class="mt-1 block text-sm font-bold text-slate-900 hover:text-blue-800">
                                {{ $eq->name }}
                            </a>
                            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                {{ $eq->description ?: 'Peralatan praktikum dan kegiatan multimedia sekolah.' }}
                            </p>

                            <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                                <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                <span class="truncate">{{ $eq->location ?: 'Ruang Inventaris Lab' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-4 py-3">
                        <a href="{{ route('borrower.catalog.show', $eq) }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Detail Alat</a>
                        @if($canBorrow)
                            <a href="{{ route('borrower.loans.create', ['equipment_id' => $eq->id]) }}" class="btn-primary btn-xs">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span>Ajukan Pinjam</span>
                            </a>
                        @else
                            <span class="text-xs font-medium text-slate-400">Tidak Dapat Dipinjam</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($equipments->hasPages())
            <div class="mt-6">
                {{ $equipments->links() }}
            </div>
        @endif
    @endif
@endsection
