@extends('layouts.app')

@section('title', 'Detail Peminjaman ' . $loan->code)
@section('header', 'Detail Transaksi Peminjaman')
@section('subheader', 'Rincian pengajuan, verifikasi persetujuan, penyerahan fisik alat, dan status pengembalian.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.loans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Daftar Peminjaman</span>
        </a>
        <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" x-data="{ rejectModal: false }">
        <!-- Left Main Detail -->
        <div class="space-y-6 lg:col-span-8">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <span class="font-mono text-sm font-bold text-slate-900">{{ $loan->code }}</span>
                        <p class="text-xs text-slate-500">Diajukan pada {{ $loan->created_at?->format('d F Y, H:i') }} WIB</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3 text-xs border-b border-slate-100">
                    <div>
                        <span class="font-semibold text-slate-500">Peminjam</span>
                        <p class="mt-0.5 font-bold text-slate-900">{{ $loan->user->name ?? '-' }}</p>
                        <p class="text-slate-500">{{ $loan->user->identity_number ?: '@' . $loan->user->username }} · {{ $loan->user->phone ?: '-' }}</p>
                        <p class="text-slate-500">{{ $loan->user->address ?: '' }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Jadwal Peminjaman</span>
                        <p class="mt-0.5 text-slate-800">Pinjam: <strong>{{ $loan->loan_date?->format('d/m/Y') }}</strong></p>
                        <p class="text-slate-800">Jatuh Tempo: <strong>{{ $loan->due_date?->format('d/m/Y') }}</strong></p>
                        @if($loan->handed_over_at)
                            <p class="mt-1 text-emerald-700 font-medium">Diserahkan: {{ $loan->handed_over_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">Petugas Terkait</span>
                        <p class="mt-0.5 text-slate-800">Verifikasi: <strong>{{ $loan->approvedBy->name ?? '-' }}</strong></p>
                        <p class="text-slate-800">Penyerahan: <strong>{{ $loan->handedOverBy->name ?? '-' }}</strong></p>
                    </div>
                </div>

                <div class="p-5 text-xs space-y-2 border-b border-slate-100">
                    <span class="font-semibold text-slate-500">Keperluan Peminjaman</span>
                    <p class="text-slate-800 leading-relaxed">{{ $loan->purpose }}</p>
                    @if($loan->notes)
                        <p class="mt-2 rounded bg-slate-50 p-2.5 text-slate-600"><strong>Catatan:</strong> {{ $loan->notes }}</p>
                    @endif
                    @if($loan->rejection_reason)
                        <div class="mt-2 rounded border border-rose-200 bg-rose-50 p-3 text-rose-800">
                            <strong>Alasan Penolakan:</strong> {{ $loan->rejection_reason }}
                        </div>
                    @endif
                </div>

                <!-- Borrowed Items Table -->
                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode Alat</th>
                                <th>Nama Peralatan</th>
                                <th>Lokasi</th>
                                <th>Kondisi Awal</th>
                                <th>Jumlah Pinjam</th>
                                <th>Stok Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loan->loanDetails as $item)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-slate-800">{{ $item->equipment->code ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $item->equipment->name ?? '-' }}</td>
                                    <td class="text-xs text-slate-500">{{ $item->equipment->location ?: '-' }}</td>
                                    <td><x-status-badge :variant="$item->condition_before" :label="ucwords(str_replace('_', ' ', $item->condition_before))" /></td>
                                    <td class="font-bold text-slate-900">{{ $item->qty }} unit</td>
                                    <td class="text-xs text-slate-600">{{ $item->equipment->qty_available ?? 0 }} tersedia</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Linked Return Card if exists -->
            @if($loan->equipmentReturn)
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Informasi Pengembalian ({{ $loan->equipmentReturn->code }})</h2>
                        <a href="{{ route('admin.returns.show', $loan->equipmentReturn) }}" class="btn-secondary btn-xs">Buka Verifikasi Pengembalian</a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-4 text-xs">
                        <div>
                            <span class="text-slate-500">Tanggal Kembali</span>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->equipmentReturn->return_date?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Keterlambatan</span>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $loan->equipmentReturn->late_days }} hari</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Total Denda</span>
                            <p class="mt-0.5 font-bold text-slate-900">{{ $loan->equipmentReturn->formatted_fine }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Status Pengembalian</span>
                            <div class="mt-1"><x-status-badge :variant="$loan->equipmentReturn->status_badge" :label="$loan->equipmentReturn->status_label" /></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Operational Control Box -->
        <div class="space-y-4 lg:col-span-4">
            <div class="panel p-5">
                <h3 class="text-sm font-bold text-slate-900">Tindakan Transaksi</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih tindakan sesuai tahapan siklus peminjaman.</p>

                <div class="mt-4 space-y-3">
                    @if($loan->status === 'pending')
                        <form method="POST" action="{{ route('admin.loans.approve', $loan) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="input-label">Catatan Persetujuan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Contoh: Ambil di loket Lab RPL 1" class="input-field">
                            </div>
                            <button type="submit" class="btn-emerald w-full">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span>Setujui & Reservasi Stok (SP)</span>
                            </button>
                        </form>

                        <button type="button" @click="rejectModal = true" class="btn-danger w-full">
                            <x-icon name="x-circle" class="h-4 w-4" />
                            <span>Tolak Pengajuan</span>
                        </button>
                    @elseif($loan->status === 'approved')
                        <form method="POST" action="{{ route('admin.loans.hand-over', $loan) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="input-label">Catatan Penyerahan Alat</label>
                                <input type="text" name="notes" value="{{ $loan->notes }}" placeholder="Kelengkapan fisik saat diserahkan" class="input-field">
                            </div>
                            <button type="submit" class="btn-blue w-full">
                                <x-icon name="clipboard-check" class="h-4 w-4" />
                                <span>Catat Penyerahan Fisik Alat</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.loans.cancel', $loan) }}" onsubmit="return confirm('Batalkan pinjaman ini dan kembalikan stok yang telah direservasi?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary w-full">Batalkan & Pulihkan Stok</button>
                        </form>
                    @elseif(in_array($loan->status, ['borrowed', 'overdue']) && !$loan->equipmentReturn)
                        <form method="POST" action="{{ route('admin.returns.store') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                            <div>
                                <label class="input-label">Tanggal Pengembalian</label>
                                <input type="date" name="return_date" value="{{ now()->toDateString() }}" class="input-field">
                            </div>
                            <button type="submit" class="btn-primary w-full">
                                <x-icon name="rotate-ccw" class="h-4 w-4" />
                                <span>Proses Pengembalian Alat</span>
                            </button>
                        </form>
                    @else
                        <div class="rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                            Transaksi ini berada pada status <strong>{{ $loan->status_label }}</strong> dan tidak memerlukan tindakan persetujuan lebih lanjut.
                        </div>
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
                <form method="POST" action="{{ route('admin.loans.reject', $loan) }}" class="space-y-4 p-5">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="input-label">Alasan Penolakan <span class="text-rose-600">*</span></label>
                        <textarea name="rejection_reason" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan secara jelas..." class="input-field"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="rejectModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-danger">Konfirmasi Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
