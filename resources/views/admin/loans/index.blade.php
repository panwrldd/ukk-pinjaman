@extends('layouts.app')

@section('title', 'Manajemen Data Peminjaman')
@section('header', 'Manajemen Data Peminjaman')
@section('subheader', 'Kelola seluruh pengajuan, persetujuan, penyerahan fisik alat, dan riwayat peminjaman laboratorium.')

@section('content')
    <div x-data="{
        createModal: false,
        items: [{ equipment_id: '', qty: 1 }],
        addItem() { this.items.push({ equipment_id: '', qty: 1 }); },
        removeItem(idx) { if (this.items.length > 1) this.items.splice(idx, 1); }
    }">
        <!-- Status Filter Tabs & Create Button -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                @php
                    $tabs = [
                        '' => 'Semua (' . $counts['all'] . ')',
                        'pending' => 'Menunggu (' . $counts['pending'] . ')',
                        'approved' => 'Disetujui (' . $counts['approved'] . ')',
                        'borrowed' => 'Dipinjam (' . $counts['borrowed'] . ')',
                        'overdue' => 'Terlambat (' . $counts['overdue'] . ')',
                        'returned' => 'Selesai (' . $counts['returned'] . ')',
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <a href="{{ route('admin.loans.index', array_filter(['status' => $key, 'search' => $search])) }}"
                       class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $status === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <button type="button" @click="createModal = true" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                <span>Input Peminjaman</span>
            </button>
        </div>

        <!-- Search & Date Filter -->
        <div class="panel mb-5 p-4">
            <form method="GET" action="{{ route('admin.loans.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                @if($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div class="relative sm:col-span-5">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode pinjam, nama peminjam, NIS/NIP, atau keperluan..." class="input-field pl-9">
                </div>
                <div class="sm:col-span-2">
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field" title="Dari Tanggal Pinjam">
                </div>
                <div class="sm:col-span-2">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field" title="Sampai Tanggal Pinjam">
                </div>
                <div class="flex items-center gap-2 sm:col-span-3">
                    <button type="submit" class="btn-primary flex-1">Filter</button>
                    @if($search !== '' || $status !== '' || $dateFrom !== '' || $dateTo !== '')
                        <a href="{{ route('admin.loans.index') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Loans Table -->
        <div class="panel">
            @if($loans->isEmpty())
                <x-empty-state icon="clipboard-list" title="Tidak Ada Data Peminjaman" description="Belum ada transaksi peminjaman pada filter yang dipilih." />
            @else
                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode & Waktu</th>
                                <th>Peminjam</th>
                                <th>Peralatan</th>
                                <th>Jadwal Pinjam</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                        <p class="text-[11px] text-slate-500">{{ $loan->created_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td>
                                        <p class="font-semibold text-slate-900">{{ $loan->user->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $loan->user->identity_number ?: $loan->user->username }}</p>
                                    </td>
                                    <td class="max-w-xs">
                                        <p class="truncate text-xs font-medium text-slate-800">
                                            {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ')')->implode(', ') }}
                                        </p>
                                        <p class="truncate text-[11px] text-slate-500">{{ $loan->purpose }}</p>
                                    </td>
                                    <td class="whitespace-nowrap text-xs">
                                        <p class="text-slate-700">{{ $loan->loan_date?->format('d/m/Y') }} &rarr; <strong>{{ $loan->due_date?->format('d/m/Y') }}</strong></p>
                                        @if($loan->current_late_days > 0 && in_array($loan->status, ['borrowed', 'overdue']))
                                            <span class="font-semibold text-rose-600">Terlambat {{ $loan->current_late_days }} hari</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-status-badge :variant="$loan->status_badge" :label="$loan->status_label" />
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.loans.show', $loan) }}" class="btn-secondary btn-xs">
                                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                                <span>Detail</span>
                                            </a>
                                            @if(in_array($loan->status, ['rejected', 'cancelled']))
                                                <form method="POST" action="{{ route('admin.loans.destroy', $loan) }}" onsubmit="return confirm('Hapus arsip peminjaman {{ $loan->code }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger-outline btn-xs" title="Hapus Arsip">
                                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($loans->hasPages())
                    <div class="border-t border-slate-200 px-5 py-3.5">
                        {{ $loans->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Admin Create Walk-in Loan Modal -->
        <div x-cloak x-show="createModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="createModal = false">
            <div @click.away="createModal = false" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Input Pengajuan Peminjaman Baru</h3>
                    <button type="button" @click="createModal = false" class="rounded p-1 text-slate-400 hover:bg-slate-100">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.loans.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="input-label">Pilih Peminjam Aktif <span class="text-rose-600">*</span></label>
                        <select name="user_id" required class="input-field">
                            <option value="">-- Pilih Peminjam --</option>
                            @foreach($borrowers as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->identity_number ?: $b->username }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="input-label">Tanggal Pinjam <span class="text-rose-600">*</span></label>
                            <input type="date" name="loan_date" value="{{ now()->toDateString() }}" min="{{ now()->toDateString() }}" required class="input-field">
                        </div>
                        <div>
                            <label class="input-label">Batas Pengembalian <span class="text-rose-600">*</span></label>
                            <input type="date" name="due_date" value="{{ now()->addDays(2)->toDateString() }}" min="{{ now()->toDateString() }}" required class="input-field">
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="input-label mb-0">Daftar Peralatan yang Dipinjam <span class="text-rose-600">*</span></label>
                            <button type="button" @click="addItem()" class="btn-secondary btn-xs">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span>Tambah Baris</span>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(row, idx) in items" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select :name="'items[' + idx + '][equipment_id]'" x-model="row.equipment_id" required class="input-field flex-1">
                                        <option value="">-- Pilih Peralatan Tersedia --</option>
                                        @foreach($availableEquipments as $eq)
                                            <option value="{{ $eq->id }}">{{ $eq->code }} — {{ $eq->name }} (Stok: {{ $eq->qty_available }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" :name="'items[' + idx + '][qty]'" x-model.number="row.qty" min="1" required class="input-field w-24" placeholder="Qty">
                                    <button type="button" @click="removeItem(idx)" x-show="items.length > 1" class="btn-danger-outline btn-xs">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="input-label">Keperluan Peminjaman <span class="text-rose-600">*</span></label>
                        <textarea name="purpose" rows="2" required minlength="5" placeholder="Jelaskan kegiatan pembelajaran / kegiatan resmi sekolah..." class="input-field"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" @click="createModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
