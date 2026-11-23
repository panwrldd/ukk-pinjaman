<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" x-data="{ submitting: false }">
    <!-- Main Inspection Form / Summary -->
    <div class="space-y-6 lg:col-span-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <span class="font-mono text-sm font-bold text-slate-900">{{ $equipmentReturn->code }}</span>
                    <p class="text-xs text-slate-500">
                        Terkait Peminjaman:
                        <a href="{{ $loanShowRoute }}" class="font-mono font-semibold text-blue-800 hover:underline">{{ $equipmentReturn->loan->code ?? '-' }}</a>
                    </p>
                </div>
                <span class="text-xs text-slate-500">Tanggal Kembali: <strong>{{ $equipmentReturn->return_date?->format('d F Y') }}</strong></span>
            </div>

            <div class="grid grid-cols-1 gap-4 border-b border-slate-100 p-5 text-xs sm:grid-cols-3">
                <div>
                    <span class="font-semibold text-slate-500">Peminjam</span>
                    <p class="mt-0.5 font-bold text-slate-900">{{ $equipmentReturn->returnedBy->name ?? '-' }}</p>
                    <p class="text-slate-500">{{ $equipmentReturn->returnedBy->identity_number ?: '@' . $equipmentReturn->returnedBy->username }}</p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Jadwal Seharusnya</span>
                    <p class="mt-0.5 text-slate-800">Pinjam: {{ $equipmentReturn->loan?->loan_date?->format('d/m/Y') }}</p>
                    <p class="text-slate-800">Jatuh Tempo: <strong>{{ $equipmentReturn->loan?->due_date?->format('d/m/Y') }}</strong></p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Petugas Pemeriksa</span>
                    <p class="mt-0.5 font-semibold text-slate-900">{{ $equipmentReturn->verifiedBy->name ?? 'Menunggu Pemeriksaan' }}</p>
                    @if($equipmentReturn->verified_at)
                        <p class="text-slate-500">{{ $equipmentReturn->verified_at->format('d/m/Y H:i') }} WIB</p>
                    @endif
                </div>
            </div>

            @if($equipmentReturn->notes)
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3 text-xs text-slate-700">
                    <strong>Catatan Pengembalian:</strong> {{ $equipmentReturn->notes }}
                </div>
            @endif

            @if($equipmentReturn->status === 'pending_verification')
                <form method="POST" action="{{ $verifyRoute }}" @submit="submitting = true">
                    @csrf
                    @method('PATCH')

                    <div class="p-5 space-y-4">
                        <div class="rounded-md border border-blue-200 bg-blue-50/60 p-3 text-xs text-slate-700">
                            <strong>Aturan Stok Berdasarkan Kondisi Fisik:</strong>
                            Unit yang dikembalikan dalam kondisi <strong>Baik</strong> atau <strong>Rusak Ringan</strong> otomatis kembali ke <em>Stok Tersedia</em>. Unit yang berstatus <strong>Rusak Berat</strong> dialihkan ke <em>Stok Rusak / Maintenance</em> dan tidak dapat dipinjam sebelum dinyatakan layak pakai.
                        </div>

                        @foreach($equipmentReturn->loan->loanDetails as $idx => $detail)
                            <div class="rounded-lg border border-slate-200 p-4" x-data="{ cond: 'baik', maxQty: {{ $detail->qty }}, dmgQty: {{ $detail->qty }} }">
                                <input type="hidden" name="details[{{ $idx }}][loan_detail_id]" value="{{ $detail->id }}">
                                <input type="hidden" name="details[{{ $idx }}][qty_returned]" value="{{ $detail->qty }}">

                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <span class="font-mono text-xs font-bold text-slate-500">{{ $detail->equipment->code ?? '-' }}</span>
                                        <h4 class="text-sm font-bold text-slate-900">{{ $detail->equipment->name ?? '-' }}</h4>
                                        <p class="text-xs text-slate-500">Kondisi awal pinjam: <strong>{{ ucwords(str_replace('_', ' ', $detail->condition_before)) }}</strong></p>
                                    </div>
                                    <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800">Dipinjam: {{ $detail->qty }} unit</span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-5">
                                        <label class="input-label">Kondisi Fisik Saat Kembali <span class="text-rose-600">*</span></label>
                                        <select name="details[{{ $idx }}][condition_after]" x-model="cond" required class="input-field">
                                            <option value="baik">Baik (Kembali ke Stok Tersedia)</option>
                                            <option value="rusak_ringan">Rusak Ringan — Masih Layak Pakai</option>
                                            <option value="rusak_berat">Rusak Berat — Masuk Maintenance</option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-3" x-show="cond === 'rusak_berat'">
                                        <label class="input-label">Unit Rusak Berat</label>
                                        <input type="number" name="details[{{ $idx }}][qty_damaged]" :value="cond === 'rusak_berat' ? dmgQty : 0" @input="dmgQty = $event.target.value" min="1" :max="maxQty" class="input-field">
                                    </div>

                                    <div :class="cond === 'rusak_berat' ? 'sm:col-span-4' : 'sm:col-span-7'">
                                        <label class="input-label">Catatan Pemeriksaan / Kerusakan</label>
                                        <input type="text" name="details[{{ $idx }}][damage_notes]" :required="cond === 'rusak_berat'" placeholder="Keterangan kondisi unit..." class="input-field">
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div>
                            <label class="input-label">Catatan Verifikasi Petugas</label>
                            <textarea name="notes" rows="2" class="input-field" placeholder="Catatan serah terima akhir pengembalian...">{{ $equipmentReturn->notes }}</textarea>
                        </div>

                        @if($finePreview['fine_amount'] > 0)
                            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="fine_paid" value="1" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-700">
                                    <span class="text-xs font-bold text-amber-950">
                                        Tandai Denda Sebesar Rp{{ number_format($finePreview['fine_amount'], 0, ',', '.') }} Telah Dibayar Tunai Saat Pengembalian
                                    </span>
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-200 px-5 py-4">
                        <button type="submit" :disabled="submitting" class="btn-emerald">
                            <x-icon name="check-circle" class="h-4 w-4" />
                            <span x-text="submitting ? 'Memproses sp_process_return...' : 'Konfirmasi & Selesaikan Pengembalian'"></span>
                        </button>
                    </div>
                </form>
            @else
                <!-- Verified Return Details -->
                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode Alat</th>
                                <th>Nama Peralatan</th>
                                <th>Qty Kembali</th>
                                <th>Unit Rusak Berat</th>
                                <th>Kondisi Akhir</th>
                                <th>Catatan Pemeriksaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equipmentReturn->returnDetails as $rd)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-slate-800">{{ $rd->equipment->code ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $rd->equipment->name ?? '-' }}</td>
                                    <td class="text-xs font-semibold">{{ $rd->qty_returned }} unit</td>
                                    <td class="text-xs font-semibold {{ $rd->qty_damaged > 0 ? 'text-rose-700' : 'text-slate-500' }}">{{ $rd->qty_damaged }} unit</td>
                                    <td><x-status-badge :variant="$rd->condition_after" :label="$rd->condition_label" /></td>
                                    <td class="text-xs text-slate-600">{{ $rd->damage_notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Fine Calculation Breakdown -->
    <div class="space-y-4 lg:col-span-4">
        <div class="panel p-5">
            <h3 class="text-sm font-bold text-slate-900">Rincian Perhitungan Denda</h3>
            <p class="mt-0.5 text-xs text-slate-500">Dihitung otomatis melalui Stored Function <code class="font-mono">fn_calculate_fine</code>.</p>

            @php
                $activeRate = $equipmentReturn->status === 'verified' && $equipmentReturn->fine_per_day_snapshot > 0
                    ? (float) $equipmentReturn->fine_per_day_snapshot
                    : \App\Models\Setting::fineRatePerDay();
            @endphp

            <dl class="mt-4 divide-y divide-slate-100 text-xs">
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500">Tanggal Jatuh Tempo</dt>
                    <dd class="font-semibold text-slate-900">{{ $equipmentReturn->loan?->due_date?->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500">Tanggal Pengembalian</dt>
                    <dd class="font-semibold text-slate-900">{{ $equipmentReturn->return_date?->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500">Jumlah Hari Terlambat</dt>
                    <dd class="font-bold {{ $finePreview['late_days'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $finePreview['late_days'] }} hari</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-slate-500">Tarif Denda / Hari</dt>
                    <dd class="font-semibold text-slate-900">Rp{{ number_format($activeRate, 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="font-bold text-slate-900">Total Denda</dt>
                    <dd class="text-base font-bold {{ $finePreview['fine_amount'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                        Rp{{ number_format($finePreview['fine_amount'], 0, ',', '.') }}
                    </dd>
                </div>
            </dl>

            @if($equipmentReturn->status === 'verified' && $equipmentReturn->fine_amount > 0)
                <div class="mt-3 pt-3 border-t border-slate-200">
                    @if($equipmentReturn->fine_paid)
                        <div class="flex items-center gap-2 rounded-md bg-emerald-50 p-3 text-xs font-semibold text-emerald-800">
                            <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
                            <span>Denda telah dinyatakan LUNAS.</span>
                        </div>
                    @else
                        <form method="POST" action="{{ $markPaidRoute }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-primary w-full">
                                <x-icon name="banknote" class="h-4 w-4" />
                                <span>Konfirmasi Pelunasan Denda</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
