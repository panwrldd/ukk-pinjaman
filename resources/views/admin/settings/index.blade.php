@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi & Tarif Denda')
@section('header', 'Pengaturan Sistem & Kebijakan Denda')
@section('subheader', 'Atur tarif denda keterlambatan harian, batas peminjaman, dan profil institusi untuk kop cetak laporan.')

@section('content')
    <div class="mx-auto max-w-4xl" x-data="{ submitting: false, fineRate: {{ (int) old('fine_rate_per_day', $settings['fine_rate_per_day'] ?? 5000) }} }">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6" @submit="submitting = true">
            @csrf
            @method('PUT')

            <!-- Fine & Loan Policy Card -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Kebijakan Peminjaman & Perhitungan Denda</h2>
                        <p class="text-xs text-slate-500">Digunakan langsung oleh Stored Function <code class="font-mono">fn_calculate_fine</code> pada database MariaDB.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-3">
                    <div>
                        <label for="fine_rate_per_day" class="input-label">Tarif Denda per Hari (Rp) <span class="text-rose-600">*</span></label>
                        <input type="number" id="fine_rate_per_day" name="fine_rate_per_day" min="0" step="500" x-model.number="fineRate" required class="input-field font-semibold @error('fine_rate_per_day') input-error @enderror">
                        @error('fine_rate_per_day') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="max_loan_days" class="input-label">Maksimal Durasi Pinjam (Hari) <span class="text-rose-600">*</span></label>
                        <input type="number" id="max_loan_days" name="max_loan_days" min="1" max="60" value="{{ old('max_loan_days', $settings['max_loan_days'] ?? 7) }}" required class="input-field @error('max_loan_days') input-error @enderror">
                        @error('max_loan_days') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="max_active_loans" class="input-label">Batas Pinjaman Aktif / Siswa <span class="text-rose-600">*</span></label>
                        <input type="number" id="max_active_loans" name="max_active_loans" min="1" max="20" value="{{ old('max_active_loans', $settings['max_active_loans'] ?? 3) }}" required class="input-field @error('max_active_loans') input-error @enderror">
                        @error('max_active_loans') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 text-xs text-slate-700">
                        <p class="font-semibold text-slate-900">Simulasi Rumus Denda Aktif:</p>
                        <p class="mt-1">
                            Rumus: <code class="font-mono font-semibold">Denda = Hari Keterlambatan × Tarif Denda per Hari</code>.
                            Contoh keterlambatan <strong>3 hari</strong> akan menghasilkan denda sebesar
                            <strong class="text-blue-900" x-text="'Rp' + (3 * (fineRate || 0)).toLocaleString('id-ID')"></strong>.
                            Transaksi yang sudah terverifikasi sebelumnya tetap mempertahankan tarif lama melalui kolom <code class="font-mono">fine_per_day_snapshot</code>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Institution Header Settings -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Identitas Institusi & Kop Laporan</h2>
                        <p class="text-xs text-slate-500">Ditampilkan pada lembar cetak laporan dan dokumen PDF resmi.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <label for="institution_name" class="input-label">Nama Sekolah / Institusi <span class="text-rose-600">*</span></label>
                        <input type="text" id="institution_name" name="institution_name" value="{{ old('institution_name', $settings['institution_name'] ?? '') }}" required class="input-field @error('institution_name') input-error @enderror">
                        @error('institution_name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="institution_unit" class="input-label">Nama Unit / Laboratorium</label>
                        <input type="text" id="institution_unit" name="institution_unit" value="{{ old('institution_unit', $settings['institution_unit'] ?? '') }}" class="input-field">
                    </div>

                    <div>
                        <label for="institution_phone" class="input-label">Nomor Telepon Resmi</label>
                        <input type="text" id="institution_phone" name="institution_phone" value="{{ old('institution_phone', $settings['institution_phone'] ?? '') }}" class="input-field">
                    </div>

                    <div>
                        <label for="institution_address" class="input-label">Alamat Lengkap</label>
                        <input type="text" id="institution_address" name="institution_address" value="{{ old('institution_address', $settings['institution_address'] ?? '') }}" class="input-field">
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-slate-200 px-6 py-4">
                    <button type="submit" :disabled="submitting" class="btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        <span x-text="submitting ? 'Menyimpan Pengaturan...' : 'Simpan Pengaturan Sistem'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
