@extends('layouts.app')

@section('title', 'Detail Peminjaman ' . $loan->code)
@section('header', 'Detail Pengajuan & Status Peminjaman')
@section('subheader', 'Informasi lengkap jadwal peminjaman, daftar alat, status verifikasi, dan pengajuan pengembalian.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('borrower.loans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Daftar Pinjaman</span>
        </a>
        <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <span class="font-mono text-sm font-bold text-slate-900">{{ $loan->code }}</span>
                        <p class="text-xs text-slate-500">Waktu Pengajuan: {{ $loan->created_at?->format('d F Y, H:i') }} WIB</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 border-b border-slate-100 p-5 text-xs sm:grid-cols-3">
                    <div>
                        <span class="font-semibold text-slate-500">Tanggal Peminjaman</span>
                        <p class="mt-0.5 font-bold text-slate-900">{{ $loan->loan_date?->translatedFormat('d F Y') }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Batas Waktu Pengembalian</span>
                        <p class="mt-0.5 font-bold {{ $loan->isLate() ? 'text-rose-700' : 'text-slate-900' }}">{{ $loan->due_date?->translatedFormat('d F Y') }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Diverifikasi Oleh</span>
                        <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->approvedBy->name ?? 'Menunggu Petugas' }}</p>
                    </div>
                </div>

                <div class="border-b border-slate-100 p-5 text-xs">
                    <span class="font-semibold text-slate-500">Keperluan Peminjaman</span>
                    <p class="mt-1 leading-relaxed text-slate-800">{{ $loan->purpose }}</p>

                    @if($loan->notes)
                        <div class="mt-3 rounded border border-blue-200 bg-blue-50/60 p-3 text-slate-700">
                            <strong>Catatan Petugas / Peminjaman:</strong> {{ $loan->notes }}
                        </div>
                    @endif

                    @if($loan->rejection_reason)
                        <div class="mt-3 rounded border border-rose-200 bg-rose-50 p-3 text-rose-800">
                            <strong>Pengajuan Ditolak:</strong> {{ $loan->rejection_reason }}
                        </div>
                    @endif
                </div>

                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode Alat</th>
                                <th>Nama Peralatan</th>
                                <th>Lokasi Pengambilan</th>
                                <th>Kondisi Awal</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loan->loanDetails as $item)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-slate-800">{{ $item->equipment->code ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $item->equipment->name ?? '-' }}</td>
                                    <td class="text-xs text-slate-600">{{ $item->equipment->location ?: '-' }}</td>
                                    <td><x-status-badge :variant="$item->condition_before" :label="ucwords(str_replace('_', ' ', $item->condition_before))" /></td>
                                    <td class="font-bold text-slate-900">{{ $item->qty }} unit</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Return Status Box if submitted -->
            @if($loan->equipmentReturn)
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Status Pengembalian ({{ $loan->equipmentReturn->code }})</h2>
                        <x-status-badge :variant="$loan->equipmentReturn->status_badge" :label="$loan->equipmentReturn->status_label" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-5 text-xs sm:grid-cols-4">
                        <div>
                            <span class="text-slate-500">Tanggal Pengajuan Kembali</span>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->equipmentReturn->return_date?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Keterlambatan</span>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->equipmentReturn->late_days }} hari</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Denda</span>
                            <p class="mt-0.5 font-bold {{ $loan->equipmentReturn->fine_amount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $loan->equipmentReturn->formatted_fine }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Petugas Pemeriksa</span>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->equipmentReturn->verifiedBy->name ?? 'Menunggu Pemeriksaan Fisik' }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Borrower Action Box -->
        <div class="space-y-4 lg:col-span-4">
            <div class="panel p-5">
                <h3 class="text-sm font-bold text-slate-900">Tindakan Peminjam</h3>

                @if($loan->status === 'pending')
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-600">
                        Pengajuan Anda sedang menunggu persetujuan Petugas. Anda masih dapat membatalkan pengajuan ini selama belum disetujui.
                    </p>
                    <form method="POST" action="{{ route('borrower.loans.cancel', $loan) }}" class="mt-4" onsubmit="return confirm('Batalkan pengajuan peminjaman ini?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-danger-outline w-full">Batalkan Pengajuan</button>
                    </form>
                @elseif($loan->status === 'approved')
                    <div class="mt-3 rounded-md border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                        Pengajuan telah <strong>Disetujui</strong>. Silakan datang ke loket laboratorium untuk serah terima fisik peralatan dengan menunjukkan kode <strong>{{ $loan->code }}</strong>.
                    </div>
                @elseif(in_array($loan->status, ['borrowed', 'overdue']) && !$loan->equipmentReturn)
                    @if($loan->isLate())
                        <div class="mt-3 rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                            Melewati jatuh tempo <strong>{{ $loan->current_late_days }} hari</strong>. Estimasi denda saat ini:
                            <strong>Rp{{ number_format($loan->current_late_days * $fineRate, 0, ',', '.') }}</strong>.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('borrower.returns.store', $loan) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="input-label">Catatan Kondisi / Pengembalian</label>
                            <textarea name="notes" rows="2" class="input-field" placeholder="Contoh: Seluruh alat lengkap siap diserahkan ke loket..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full">
                            <x-icon name="rotate-ccw" class="h-4 w-4" />
                            <span>Ajukan Pengembalian Alat</span>
                        </button>
                    </form>
                @elseif($loan->equipmentReturn && $loan->equipmentReturn->status === 'pending_verification')
                    <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Pengajuan pengembalian telah tercatat. Silakan serahkan fisik peralatan kepada Petugas untuk diperiksa kondisinya.
                    </div>
                @else
                    <p class="mt-2 text-xs text-slate-600">Transaksi ini telah selesai dengan status <strong>{{ $loan->status_label }}</strong>.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
