@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Alat')
@section('header', 'Formulir Pengajuan Peminjaman Alat')
@section('subheader', 'Pilih peralatan yang dibutuhkan, tentukan jumlah dan tanggal pengembalian sesuai ketentuan laboratorium.')

@section('content')
    @php
        $equipmentMeta = $availableEquipments->mapWithKeys(fn($eq) => [
            $eq->id => [
                'code' => $eq->code,
                'name' => $eq->name,
                'available' => $eq->qty_available,
                'location' => $eq->location ?: 'Loket Lab',
            ]
        ]);
        $initialItems = old('items') ?: [['equipment_id' => $selectedEquipmentId ?: '', 'qty' => 1]];
    @endphp

    <div class="mx-auto max-w-4xl" x-data="{
        submitting: false,
        catalog: @js($equipmentMeta),
        items: @js($initialItems),
        addRow() {
            this.items.push({ equipment_id: '', qty: 1 });
        },
        removeRow(idx) {
            if (this.items.length > 1) {
                this.items.splice(idx, 1);
            }
        },
        maxFor(eqId) {
            return eqId && this.catalog[eqId] ? this.catalog[eqId].available : 99;
        }
    }">
        <div class="mb-4">
            <a href="{{ route('borrower.catalog.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                <span>Kembali ke Katalog Alat</span>
            </a>
        </div>

        <form method="POST" action="{{ route('borrower.loans.store') }}" class="grid grid-cols-1 gap-6 lg:grid-cols-12" @submit="submitting = true">
            @csrf

            <div class="space-y-6 lg:col-span-8">
                <!-- Equipment Selection Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">1. Daftar Peralatan yang Dipinjam</h2>
                            <p class="text-xs text-slate-500">Jumlah yang diajukan tidak boleh melebihi stok tersedia.</p>
                        </div>
                        <button type="button" @click="addRow()" class="btn-secondary btn-xs">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            <span>Tambah Alat</span>
                        </button>
                    </div>

                    <div class="space-y-3 p-5">
                        @if($availableEquipments->isEmpty())
                            <p class="text-xs text-rose-700">Maaf, saat ini seluruh peralatan sedang dipinjam atau dalam perawatan.</p>
                        @else
                            <template x-for="(row, idx) in items" :key="idx">
                                <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3.5">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 items-end">
                                        <div class="sm:col-span-8">
                                            <label class="input-label">Peralatan <span class="text-rose-600">*</span></label>
                                            <select :name="'items[' + idx + '][equipment_id]'" x-model="row.equipment_id" required class="input-field">
                                                <option value="">-- Pilih Peralatan --</option>
                                                @foreach($availableEquipments as $eq)
                                                    <option value="{{ $eq->id }}">{{ $eq->code }} — {{ $eq->name }} (Tersedia: {{ $eq->qty_available }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="sm:col-span-3">
                                            <label class="input-label">Jumlah Unit <span class="text-rose-600">*</span></label>
                                            <input type="number" :name="'items[' + idx + '][qty]'" x-model.number="row.qty" min="1" :max="maxFor(row.equipment_id)" required class="input-field">
                                        </div>

                                        <div class="sm:col-span-1 flex justify-end">
                                            <button type="button" @click="removeRow(idx)" x-show="items.length > 1" class="btn-danger-outline btn-xs py-2" title="Hapus Baris">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <template x-if="row.equipment_id && catalog[row.equipment_id]">
                                        <p class="mt-2 text-[11px] text-slate-500">
                                            Stok siap pinjam: <strong class="text-emerald-700" x-text="catalog[row.equipment_id].available + ' unit'"></strong>
                                            · Lokasi: <span x-text="catalog[row.equipment_id].location"></span>
                                        </p>
                                    </template>
                                </div>
                            </template>
                        @endif
                        @error('items') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Schedule & Purpose Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">2. Jadwal & Keperluan Peminjaman</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <label for="loan_date" class="input-label">Tanggal Mulai Pinjam <span class="text-rose-600">*</span></label>
                            <input type="date" id="loan_date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required class="input-field @error('loan_date') input-error @enderror">
                            @error('loan_date') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="due_date" class="input-label">Rencana Tanggal Pengembalian <span class="text-rose-600">*</span></label>
                            <input type="date" id="due_date" name="due_date" value="{{ old('due_date', now()->addDays(2)->toDateString()) }}" min="{{ now()->toDateString() }}" required class="input-field @error('due_date') input-error @enderror">
                            @error('due_date') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="purpose" class="input-label">Keperluan Kegiatan / Mata Pelajaran <span class="text-rose-600">*</span></label>
                            <textarea id="purpose" name="purpose" rows="3" required minlength="5" placeholder="Contoh: Praktikum Produksi Audio Visual Kelas XII / Dokumentasi Kegiatan OSIS..." class="input-field @error('purpose') input-error @enderror">{{ old('purpose') }}</textarea>
                            @error('purpose') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="notes" class="input-label">Catatan Tambahan (Opsional)</label>
                            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" placeholder="Jam rencana pengambilan atau keterangan tambahan..." class="input-field">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Rules & Submit Box -->
            <div class="space-y-4 lg:col-span-4">
                <div class="panel p-5">
                    <h3 class="text-sm font-bold text-slate-900">Ketentuan Peminjaman</h3>
                    <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-slate-600">
                        <li class="flex items-start gap-2">
                            <x-icon name="clock" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span>Durasi peminjaman maksimal <strong>{{ $maxLoanDays }} hari</strong> per transaksi.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="shield-check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span>Pengajuan akan berstatus <strong>Pending</strong> hingga disetujui oleh Petugas Laboratorium.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="banknote" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span>Keterlambatan pengembalian dikenakan denda sebesar <strong>Rp{{ number_format($fineRate, 0, ',', '.') }}/hari</strong>.</span>
                        </li>
                    </ul>

                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <button type="submit" :disabled="submitting" class="btn-primary w-full py-2.5">
                            <x-icon name="send" class="h-4 w-4" />
                            <span x-text="submitting ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan Peminjaman'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
