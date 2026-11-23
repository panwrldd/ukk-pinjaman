@extends('layouts.app')

@section('title', 'Detail Peralatan — ' . $equipment->name)
@section('header', 'Detail Inventaris Peralatan')
@section('subheader', 'Informasi spesifikasi, distribusi stok unit, pemulihan servis, dan histori peminjaman alat.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.equipments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Inventaris</span>
        </a>
        <a href="{{ route('admin.equipments.edit', $equipment) }}" class="btn-primary btn-xs">
            <x-icon name="pencil" class="h-3.5 w-3.5" />
            <span>Edit Data Alat</span>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Equipment Profile Card -->
        <div class="space-y-6 lg:col-span-5">
            <div class="panel p-5">
                <div class="flex items-start gap-4">
                    @if($equipment->photo_url)
                        <img src="{{ $equipment->photo_url }}" alt="{{ $equipment->name }}" class="h-24 w-24 shrink-0 rounded-lg border border-slate-200 object-cover">
                    @else
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400">
                            <x-icon name="camera" class="h-8 w-8" />
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="font-mono text-xs font-bold text-blue-800">{{ $equipment->code }}</span>
                        <h2 class="mt-0.5 text-base font-bold text-slate-900">{{ $equipment->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $equipment->category->name ?? '-' }}</p>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <x-status-badge :variant="$equipment->status" :label="$equipment->status_label" />
                            <x-status-badge :variant="$equipment->condition" :label="'Kondisi: ' . $equipment->condition_label" />
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-4 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
                    <div>
                        <span class="block text-[10px] font-semibold uppercase text-slate-500">Total</span>
                        <span class="text-base font-bold text-slate-900">{{ $equipment->qty_total }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold uppercase text-emerald-700">Tersedia</span>
                        <span class="text-base font-bold text-emerald-700">{{ $equipment->qty_available }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold uppercase text-blue-700">Dipinjam</span>
                        <span class="text-base font-bold text-blue-700">{{ $equipment->qty_borrowed }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold uppercase text-rose-700">Rusak</span>
                        <span class="text-base font-bold text-rose-700">{{ $equipment->qty_damaged }}</span>
                    </div>
                </div>

                <dl class="mt-5 divide-y divide-slate-100 text-xs">
                    <div class="py-2.5">
                        <dt class="font-semibold text-slate-500">Lokasi Penyimpanan</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $equipment->location ?: '-' }}</dd>
                    </div>
                    <div class="py-2.5">
                        <dt class="font-semibold text-slate-500">Deskripsi & Kelengkapan</dt>
                        <dd class="mt-0.5 leading-relaxed text-slate-700">{{ $equipment->description ?: '-' }}</dd>
                    </div>
                    <div class="py-2.5">
                        <dt class="font-semibold text-slate-500">Catatan SOP</dt>
                        <dd class="mt-0.5 leading-relaxed text-slate-700">{{ $equipment->notes ?: '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Maintenance Recovery Card (Appears when damaged units > 0) -->
            @if($equipment->qty_damaged > 0)
                <div class="panel border-amber-200 bg-amber-50/40 p-5">
                    <div class="flex items-center gap-2 text-amber-900">
                        <x-icon name="wrench" class="h-4 w-4 text-amber-700" />
                        <h3 class="text-sm font-bold">Pemulihan Unit Selesai Perbaikan</h3>
                    </div>
                    <p class="mt-1 text-xs leading-relaxed text-slate-600">
                        Terdapat <strong>{{ $equipment->qty_damaged }} unit</strong> yang dikembalikan dalam kondisi rusak berat dan tidak masuk ke stok tersedia. Jika perbaikan telah selesai, pulihkan unit ke stok layak pakai:
                    </p>

                    <form method="POST" action="{{ route('admin.equipments.restore-maintenance', $equipment) }}" class="mt-4 space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="input-label">Jumlah Unit Dipulihkan</label>
                                <input type="number" name="qty_restore" min="1" max="{{ $equipment->qty_damaged }}" value="{{ $equipment->qty_damaged }}" required class="input-field">
                            </div>
                            <div>
                                <label class="input-label">Catatan Servis</label>
                                <input type="text" name="notes" placeholder="Contoh: Suku cadang telah diganti" class="input-field">
                            </div>
                        </div>
                        <button type="submit" class="btn-emerald w-full">
                            <x-icon name="check-circle" class="h-4 w-4" />
                            <span>Nyatakan Layak Pakai & Kembalikan ke Stok Tersedia</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Loan Usage History -->
        <div class="panel lg:col-span-7">
            <div class="panel-header">
                <h2 class="panel-title">Riwayat Peminjaman Peralatan Ini</h2>
            </div>
            @if($equipment->loanDetails->isEmpty())
                <x-empty-state icon="clipboard-list" title="Belum Pernah Dipinjam" description="Peralatan ini belum memiliki catatan riwayat peminjaman." />
            @else
                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Peminjam</th>
                                <th>Qty</th>
                                <th>Tgl Pinjam</th>
                                <th>Kondisi Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equipment->loanDetails as $detail)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.loans.show', $detail->loan_id) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">
                                            {{ $detail->loan->code ?? '-' }}
                                        </a>
                                    </td>
                                    <td class="text-xs font-medium text-slate-900">{{ $detail->loan->user->name ?? '-' }}</td>
                                    <td class="text-xs font-semibold">{{ $detail->qty }} unit</td>
                                    <td class="text-xs text-slate-600">{{ $detail->loan?->loan_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        @if($detail->returnDetail)
                                            <x-status-badge :variant="$detail->returnDetail->condition_after" :label="$detail->returnDetail->condition_label" />
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($detail->loan)
                                            <x-status-badge :variant="$detail->loan->status_badge" :label="$detail->loan->status_label" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
