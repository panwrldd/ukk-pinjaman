@extends('layouts.app')

@section('title', 'Meja Kerja Petugas')
@section('header', 'Meja Operasional Petugas Laboratorium')
@section('subheader', 'Antrian verifikasi pengajuan peminjaman, penyerahan fisik alat, dan pemeriksaan pengembalian.')

@section('content')
    <div x-data="{ tab: '{{ $metrics['pending_approvals'] > 0 ? 'pending' : ($metrics['pending_returns'] > 0 ? 'returns' : 'handover') }}' }">
        <!-- Actionable Queue Selector Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <button type="button" @click="tab = 'pending'" :class="tab === 'pending' ? 'ring-2 ring-slate-900 border-slate-900' : ''" class="panel p-4 text-left transition hover:border-slate-400">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-800">Menunggu Persetujuan</span>
                    <x-icon name="clock" class="h-4 w-4 text-amber-600" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $metrics['pending_approvals'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Pengajuan baru perlu dicek</p>
            </button>

            <button type="button" @click="tab = 'handover'" :class="tab === 'handover' ? 'ring-2 ring-slate-900 border-slate-900' : ''" class="panel p-4 text-left transition hover:border-slate-400">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-blue-800">Siap Diserahkan</span>
                    <x-icon name="clipboard-check" class="h-4 w-4 text-blue-700" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $metrics['ready_for_handover'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Stok sudah direservasi</p>
            </button>

            <button type="button" @click="tab = 'returns'" :class="tab === 'returns' ? 'ring-2 ring-slate-900 border-slate-900' : ''" class="panel p-4 text-left transition hover:border-slate-400">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-emerald-800">Verifikasi Kembali</span>
                    <x-icon name="rotate-ccw" class="h-4 w-4 text-emerald-700" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $metrics['pending_returns'] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Cek kondisi fisik & denda</p>
            </button>

            <button type="button" @click="tab = 'overdue'" :class="tab === 'overdue' ? 'ring-2 ring-slate-900 border-slate-900' : ''" class="panel p-4 text-left transition hover:border-slate-400">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-700">Sedang Dipinjam / Telat</span>
                    <x-icon name="alert-triangle" class="h-4 w-4 text-rose-600" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $metrics['currently_borrowed'] }} <span class="text-sm font-semibold text-rose-600">({{ $metrics['overdue_count'] }} telat)</span></p>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ $metrics['damaged_units'] }} unit dalam servis</p>
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left Operational Queue Panel -->
            <div class="panel lg:col-span-8">
                <!-- Tab 1: Pending Approvals -->
                <div x-show="tab === 'pending'">
                    <div class="panel-header">
                        <h2 class="panel-title">Antrian Pengajuan Menunggu Persetujuan</h2>
                        <a href="{{ route('staff.loans.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-blue-800 hover:underline">Buka Semua</a>
                    </div>
                    @if($pendingLoans->isEmpty())
                        <x-empty-state icon="check-circle" title="Antrian Persetujuan Bersih" description="Tidak ada pengajuan peminjaman baru yang menunggu verifikasi saat ini." />
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($pendingLoans as $loan)
                                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('staff.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                            <span class="text-xs font-semibold text-slate-900">{{ $loan->user->name ?? '-' }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-700">
                                            {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ' unit)')->implode(', ') }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">Jadwal: {{ $loan->loan_date?->format('d/m/Y') }} s/d {{ $loan->due_date?->format('d/m/Y') }} · {{ $loan->purpose }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('staff.loans.approve', $loan) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-emerald btn-xs">Setujui</button>
                                        </form>
                                        <a href="{{ route('staff.loans.show', $loan) }}" class="btn-secondary btn-xs">Detail / Tolak</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tab 2: Ready for Physical Handover -->
                <div x-cloak x-show="tab === 'handover'">
                    <div class="panel-header">
                        <h2 class="panel-title">Siap Diserahkan Secara Fisik kepada Peminjam</h2>
                        <a href="{{ route('staff.loans.index', ['status' => 'approved']) }}" class="text-xs font-semibold text-blue-800 hover:underline">Buka Semua</a>
                    </div>
                    @if($readyHandoverLoans->isEmpty())
                        <x-empty-state icon="clipboard-check" title="Tidak Ada Antrian Penyerahan" description="Belum ada peminjaman berstatus disetujui yang menunggu pengambilan fisik." />
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($readyHandoverLoans as $loan)
                                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('staff.loans.show', $loan) }}" class="font-mono text-xs font-bold text-blue-800 hover:underline">{{ $loan->code }}</a>
                                            <span class="text-xs font-semibold text-slate-900">{{ $loan->user->name ?? '-' }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-700">
                                            {{ $loan->loanDetails->map(fn($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ' unit)')->implode(', ') }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('staff.loans.hand-over', $loan) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-blue btn-xs">Catat Penyerahan Alat</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tab 3: Pending Returns Verification -->
                <div x-cloak x-show="tab === 'returns'">
                    <div class="panel-header">
                        <h2 class="panel-title">Pengembalian Menunggu Pemeriksaan Kondisi</h2>
                        <a href="{{ route('staff.returns.index', ['status' => 'pending_verification']) }}" class="text-xs font-semibold text-blue-800 hover:underline">Buka Semua</a>
                    </div>
                    @if($pendingReturns->isEmpty())
                        <x-empty-state icon="rotate-ccw" title="Tidak Ada Pengembalian Menunggu" description="Seluruh pengajuan pengembalian telah selesai diperiksa." />
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($pendingReturns as $ret)
                                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-slate-900">{{ $ret->code }}</span>
                                            <span class="text-xs text-slate-500">({{ $ret->loan->code ?? '-' }})</span>
                                        </div>
                                        <p class="mt-0.5 text-xs font-semibold text-slate-800">{{ $ret->returnedBy->name ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-500">Jatuh tempo: {{ $ret->loan?->due_date?->format('d/m/Y') }} · Dikembalikan: {{ $ret->return_date?->format('d/m/Y') }}</p>
                                    </div>
                                    <a href="{{ route('staff.returns.show', $ret) }}" class="btn-primary btn-xs">Periksa Fisik & Verifikasi</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tab 4: Overdue Active Loans -->
                <div x-cloak x-show="tab === 'overdue'">
                    <div class="panel-header">
                        <h2 class="panel-title">Peminjaman Melewati Batas Waktu (Overdue)</h2>
                        <a href="{{ route('staff.loans.index', ['status' => 'overdue']) }}" class="text-xs font-semibold text-blue-800 hover:underline">Buka Semua</a>
                    </div>
                    @if($overdueLoans->isEmpty())
                        <x-empty-state icon="check-circle" title="Tidak Ada Pinjaman Terlambat" description="Seluruh peminjaman aktif masih berada dalam tenggat waktu." />
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($overdueLoans as $loan)
                                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('staff.loans.show', $loan) }}" class="font-mono text-xs font-bold text-rose-700 hover:underline">{{ $loan->code }}</a>
                                            <x-status-badge variant="overdue" :label="$loan->current_late_days . ' hari terlambat'" />
                                        </div>
                                        <p class="mt-0.5 text-xs font-semibold text-slate-900">{{ $loan->user->name ?? '-' }} · {{ $loan->user->phone ?: '-' }}</p>
                                    </div>
                                    <a href="{{ route('staff.loans.show', $loan) }}" class="btn-secondary btn-xs">Tindak Lanjuti</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Activity Feed -->
            <div class="panel lg:col-span-4">
                <div class="panel-header">
                    <h2 class="panel-title">Aktivitas Operasional Terbaru</h2>
                </div>
                @if($recentActivities->isEmpty())
                    <x-empty-state icon="activity" title="Belum Ada Aktivitas" description="Log transaksi terbaru akan muncul di sini." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentActivities as $act)
                            <div class="px-4 py-3 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900">{{ $act->user->name ?? 'Trigger DB' }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $act->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 leading-relaxed text-slate-600">{{ $act->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
