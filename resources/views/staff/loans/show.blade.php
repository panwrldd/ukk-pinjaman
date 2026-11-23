@extends('layouts.app')

@section('title', 'Periksa Pengajuan ' . $loan->code)
@section('header', 'Pemeriksaan Pengajuan & Penyerahan Alat')
@section('subheader', 'Validasi ketersediaan stok, setujui/tolak pengajuan, dan catat penyerahan fisik peralatan.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('staff.loans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Antrian Peminjaman</span>
        </a>
        <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" x-data="{ rejectModal: false }">
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
                        <span class="font-semibold text-slate-500">Identitas Peminjam</span>
                        <p class="mt-0.5 font-bold text-slate-900">{{ $loan->user->name ?? '-' }}</p>
                        <p class="text-slate-500">ID: {{ $loan->user->identity_number ?: '-' }} · Telp: {{ $loan->user->phone ?: '-' }}</p>
                        <p class="text-slate-500">{{ $loan->user->address ?: '' }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Jadwal Peminjaman</span>
                        <p class="mt-0.5 text-slate-800">Rencana Pinjam: <strong>{{ $loan->loan_date?->format('d/m/Y') }}</strong></p>
                        <p class="text-slate-800">Batas Kembali: <strong>{{ $loan->due_date?->format('d/m/Y') }}</strong></p>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Riwayat Petugas</span>
                        <p class="mt-0.5 text-slate-800">Persetujuan: <strong>{{ $loan->approvedBy->name ?? '-' }}</strong></p>
                        <p class="text-slate-800">Penyerahan: <strong>{{ $loan->handedOverBy->name ?? '-' }}</strong></p>
                    </div>
                </div>

                <div class="border-b border-slate-100 p-5 text-xs">
                    <span class="font-semibold text-slate-500">Keperluan Kegiatan</span>
                    <p class="mt-1 leading-relaxed text-slate-800">{{ $loan->purpose }}</p>
                    @if($loan->rejection_reason)
                        <div class="mt-3 rounded border border-rose-200 bg-rose-50 p-3 text-rose-800">
                            <strong>Alasan Penolakan:</strong> {{ $loan->rejection_reason }}
                        </div>
                    @endif
                </div>

                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Peralatan</th>
                                <th>Lokasi Rak/Lemari</th>
                                <th>Jumlah Diminta</th>
                                <th>Stok Tersedia Gudang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loan->loanDetails as $item)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-slate-800">{{ $item->equipment->code ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $item->equipment->name ?? '-' }}</td>
                                    <td class="text-xs text-slate-600">{{ $item->equipment->location ?: '-' }}</td>
                                    <td class="font-bold text-slate-900">{{ $item->qty }} unit</td>
                                    <td>
                                        <span class="text-xs font-semibold {{ ($item->equipment->qty_available ?? 0) >= $item->qty ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $item->equipment->qty_available ?? 0 }} unit tersedia
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel p-5">
                <h3 class="text-sm font-bold text-slate-900">Keputusan Petugas</h3>
                <div class="mt-4 space-y-3">
                    @if($loan->status === 'pending')
                        <form method="POST" action="{{ route('staff.loans.approve', $loan) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="input-label">Catatan Pengambilan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Contoh: Silakan ambil pukul 08.00 di Studio MM" class="input-field">
                            </div>
                            <button type="submit" class="btn-emerald w-full">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span>Setujui Pengajuan</span>
                            </button>
                        </form>

                        <button type="button" @click="rejectModal = true" class="btn-danger w-full">
                            <x-icon name="x-circle" class="h-4 w-4" />
                            <span>Tolak dengan Alasan</span>
                        </button>
                    @elseif($loan->status === 'approved')
                        <form method="POST" action="{{ route('staff.loans.hand-over', $loan) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="input-label">Catatan Serah Terima Fisik</label>
                                <input type="text" name="notes" value="{{ $loan->notes }}" placeholder="Kondisi unit lengkap saat diserahkan" class="input-field">
                            </div>
                            <button type="submit" class="btn-blue w-full">
                                <x-icon name="clipboard-check" class="h-4 w-4" />
                                <span>Konfirmasi Alat Telah Diserahkan</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('staff.loans.cancel', $loan) }}" onsubmit="return confirm('Batalkan pinjaman yang tidak jadi diambil dan kembalikan stok?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary w-full">Batal Diambil (Pulihkan Stok)</button>
                        </form>
                    @elseif(in_array($loan->status, ['borrowed', 'overdue']) && !$loan->equipmentReturn)
                        <form method="POST" action="{{ route('staff.returns.initiate', $loan) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">
                                <x-icon name="rotate-ccw" class="h-4 w-4" />
                                <span>Terima Pengembalian di Loket</span>
                            </button>
                        </form>
                    @elseif($loan->equipmentReturn)
                        <a href="{{ route('staff.returns.show', $loan->equipmentReturn) }}" class="btn-primary w-full">
                            <span>Buka Data Pengembalian ({{ $loan->equipmentReturn->code }})</span>
                        </a>
                    @else
                        <p class="rounded bg-slate-50 p-3 text-xs text-slate-600">Tidak ada tindakan lanjutan untuk status <strong>{{ $loan->status_label }}</strong>.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reject Reason Modal -->
        <div x-cloak x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="rejectModal = false" class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Tolak Pengajuan {{ $loan->code }}</h3>
                    <button type="button" @click="rejectModal = false" class="rounded p-1 text-slate-400 hover:bg-slate-100">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <form method="POST" action="{{ route('staff.loans.reject', $loan) }}" class="space-y-4 p-5">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="input-label">Alasan Penolakan <span class="text-rose-600">*</span></label>
                        <textarea name="rejection_reason" rows="3" required minlength="5" placeholder="Jelaskan alasan penolakan kepada peminjam..." class="input-field"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="rejectModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-danger">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
