@extends('layouts.app')

@php
    $isEdit = $equipment->exists;
@endphp

@section('title', $isEdit ? 'Edit Peralatan' : 'Tambah Peralatan Baru')
@section('header', $isEdit ? 'Perbarui Inventaris Alat' : 'Registrasi Peralatan Baru')
@section('subheader', 'Pastikan kode inventaris, jumlah stok, kondisi, dan lokasi penyimpanan terisi akurat.')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-4">
            <a href="{{ route('admin.equipments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                <span>Kembali ke Daftar Inventaris</span>
            </a>
        </div>

        <div class="panel" x-data="{
            submitting: false,
            previewUrl: @js($equipment->photo_url),
            handleFile(event) {
                const file = event.target.files[0];
                if (file) {
                    this.previewUrl = URL.createObjectURL(file);
                }
            }
        }">
            <div class="panel-header">
                <h2 class="panel-title">{{ $isEdit ? 'Edit Peralatan: ' . $equipment->code : 'Formulir Data Peralatan' }}</h2>
            </div>

            <form method="POST" action="{{ $isEdit ? route('admin.equipments.update', $equipment) : route('admin.equipments.store') }}" enctype="multipart/form-data" class="space-y-6 p-6" @submit="submitting = true">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <label for="code" class="input-label">Kode Inventaris <span class="text-rose-600">*</span></label>
                        <input type="text" id="code" name="code" value="{{ old('code', $equipment->code) }}" required class="input-field font-mono uppercase @error('code') input-error @enderror" placeholder="Contoh: EQP-KAM-004">
                        @error('code') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-8">
                        <label for="name" class="input-label">Nama Peralatan <span class="text-rose-600">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required class="input-field @error('name') input-error @enderror" placeholder="Contoh: Kamera Mirrorless Sony Alpha a6400 Kit">
                        @error('name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-6">
                        <label for="category_id" class="input-label">Kategori Alat <span class="text-rose-600">*</span></label>
                        <select id="category_id" name="category_id" required class="input-field @error('category_id') input-error @enderror">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) old('category_id', $equipment->category_id) === (string) $cat->id)>{{ $cat->code }} — {{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-6">
                        <label for="location" class="input-label">Lokasi Penyimpanan</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $equipment->location) }}" class="input-field @error('location') input-error @enderror" placeholder="Contoh: Lemari Dry Cabinet A-01 (Studio MM)">
                        @error('location') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <!-- Stock & Condition Section -->
                    <div class="sm:col-span-3">
                        <label for="qty_total" class="input-label">Total Stok <span class="text-rose-600">*</span></label>
                        <input type="number" id="qty_total" name="qty_total" min="1" value="{{ old('qty_total', $equipment->qty_total) }}" required class="input-field @error('qty_total') input-error @enderror">
                        @error('qty_total') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    @if(!$isEdit)
                        <div class="sm:col-span-3">
                            <label for="qty_available" class="input-label">Stok Tersedia Awal</label>
                            <input type="number" id="qty_available" name="qty_available" min="0" value="{{ old('qty_available', $equipment->qty_available) }}" class="input-field @error('qty_available') input-error @enderror">
                            @error('qty_available') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="sm:col-span-3">
                            <label class="input-label">Sedang Dipinjam</label>
                            <input type="text" value="{{ $equipment->qty_borrowed }} unit" disabled class="input-field">
                        </div>
                    @endif

                    <div class="sm:col-span-3">
                        <label for="condition" class="input-label">Kondisi Dominan <span class="text-rose-600">*</span></label>
                        <select id="condition" name="condition" required class="input-field @error('condition') input-error @enderror">
                            <option value="baik" @selected(old('condition', $equipment->condition) === 'baik')>Baik</option>
                            <option value="rusak_ringan" @selected(old('condition', $equipment->condition) === 'rusak_ringan')>Rusak Ringan</option>
                            <option value="rusak_berat" @selected(old('condition', $equipment->condition) === 'rusak_berat')>Rusak Berat</option>
                        </select>
                        @error('condition') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="status" class="input-label">Status Ketersediaan <span class="text-rose-600">*</span></label>
                        <select id="status" name="status" required class="input-field @error('status') input-error @enderror">
                            <option value="tersedia" @selected(old('status', $equipment->status) === 'tersedia')>Tersedia</option>
                            <option value="tidak_tersedia" @selected(old('status', $equipment->status) === 'tidak_tersedia')>Tidak Tersedia</option>
                            <option value="maintenance" @selected(old('status', $equipment->status) === 'maintenance')>Maintenance</option>
                        </select>
                        @error('status') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-12">
                        <label for="description" class="input-label">Spesifikasi & Deskripsi Alat</label>
                        <textarea id="description" name="description" rows="3" class="input-field @error('description') input-error @enderror" placeholder="Kelengkapan unit, spesifikasi teknis singkat, dll.">{{ old('description', $equipment->description) }}</textarea>
                        @error('description') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-12">
                        <label for="notes" class="input-label">Catatan Penggunaan / SOP Peminjaman</label>
                        <textarea id="notes" name="notes" rows="2" class="input-field @error('notes') input-error @enderror" placeholder="Contoh: Wajib dikembalikan beserta baterai cadangan dan tas pelindung.">{{ old('notes', $equipment->notes) }}</textarea>
                        @error('notes') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <!-- Photo Upload -->
                    <div class="sm:col-span-12 border-t border-slate-200 pt-5">
                        <label class="input-label">Foto Peralatan (JPG / PNG / WEBP · Maks 2 MB)</label>
                        <div class="mt-2 flex flex-wrap items-center gap-5">
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" alt="Preview" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!previewUrl">
                                    <x-icon name="camera" class="h-7 w-7 text-slate-300" />
                                </template>
                            </div>
                            <div class="flex-1 min-w-[220px]">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @change="handleFile($event)" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800">
                                @if($isEdit && $equipment->photo)
                                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-rose-700 cursor-pointer">
                                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-600">
                                        <span>Hapus foto saat ini</span>
                                    </label>
                                @endif
                                @error('photo') <p class="error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
                    <a href="{{ route('admin.equipments.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" :disabled="submitting" class="btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        <span x-text="submitting ? 'Menyimpan...' : '{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Peralatan' }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
