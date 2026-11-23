<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Supported report types and role access definitions.
     *
     * @return array<string, array{title: string, subtitle: string, roles: array<int, string>}>
     */
    public static function availableReports(?User $user = null): array
    {
        $catalog = [
            'loans' => [
                'title' => 'Laporan Peminjaman Alat',
                'subtitle' => 'Rekapitulasi seluruh pengajuan dan transaksi peminjaman peralatan laboratorium.',
                'roles' => ['admin', 'petugas'],
            ],
            'returns' => [
                'title' => 'Laporan Pengembalian Alat',
                'subtitle' => 'Data verifikasi pengembalian, pemeriksaan kondisi fisik alat, dan status penyelesaian.',
                'roles' => ['admin', 'petugas'],
            ],
            'overdue' => [
                'title' => 'Laporan Keterlambatan',
                'subtitle' => 'Daftar peminjaman yang melewati batas waktu pengembalian (aktif maupun selesai terlambat).',
                'roles' => ['admin', 'petugas'],
            ],
            'fines' => [
                'title' => 'Laporan Denda Keterlambatan',
                'subtitle' => 'Rincian perhitungan denda keterlambatan beserta status pembayaran tunai/lunas.',
                'roles' => ['admin', 'petugas'],
            ],
            'equipments' => [
                'title' => 'Laporan Inventaris Data Alat',
                'subtitle' => 'Posisi stok total, stok tersedia, unit sedang dipinjam, dan unit rusak/maintenance.',
                'roles' => ['admin', 'petugas'],
            ],
            'categories' => [
                'title' => 'Laporan Data Kategori Alat',
                'subtitle' => 'Daftar klasifikasi inventaris peralatan beserta ringkasan jumlah jenis dan unit alat.',
                'roles' => ['admin', 'petugas'],
            ],
            'users' => [
                'title' => 'Laporan Data Pengguna Sistem',
                'subtitle' => 'Daftar akun Administrator, Petugas Laboratorium, dan Peminjam terdaftar.',
                'roles' => ['admin'],
            ],
        ];

        if (!$user) {
            return $catalog;
        }

        return array_filter(
            $catalog,
            fn (array $meta) => in_array($user->role, $meta['roles'], true)
        );
    }

    /**
     * Build complete dataset, summary metrics, headings, and tabular rows for a given report type.
     *
     * @return array{meta: array<string, mixed>, records: Collection, summary: array<int, array{label: string, value: string}>, headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function buildReport(string $type, array $filters = []): array
    {
        LoanService::syncOverdueStatuses();

        $catalog = static::availableReports();
        $meta = $catalog[$type] ?? $catalog['loans'];

        return match ($type) {
            'equipments' => static::buildEquipmentReport($meta, $filters),
            'categories' => static::buildCategoryReport($meta, $filters),
            'users' => static::buildUserReport($meta, $filters),
            'returns' => static::buildReturnReport($meta, $filters),
            'overdue' => static::buildOverdueReport($meta, $filters),
            'fines' => static::buildFineReport($meta, $filters),
            default => static::buildLoanReport($meta, $filters),
        };
    }

    private static function buildLoanReport(array $meta, array $filters): array
    {
        $query = Loan::with(['user', 'approvedBy', 'handedOverBy', 'loanDetails.equipment.category'])
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('loan_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('loan_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['category_id'] ?? null), function (Builder $q) use ($filters) {
                $q->whereHas('loanDetails.equipment', fn (Builder $eq) => $eq->where('category_id', $filters['category_id']));
            })
            ->orderByDesc('loan_date')
            ->orderByDesc('id');

        $records = $query->get();

        $totalUnits = $records->sum(fn (Loan $l) => $l->loanDetails->sum('qty'));

        $headings = [
            'No',
            'Kode Peminjaman',
            'Nama Peminjam',
            'Identitas (NIS/NIP)',
            'Daftar Alat & Jumlah',
            'Tgl Pinjam',
            'Batas Kembali',
            'Petugas Verifikasi',
            'Status',
        ];

        $rows = [];
        foreach ($records as $idx => $loan) {
            $itemsText = $loan->loanDetails
                ->map(fn ($d) => ($d->equipment->name ?? '-') . ' (' . $d->qty . ' unit)')
                ->implode('; ');

            $rows[] = [
                $idx + 1,
                $loan->code,
                $loan->user->name ?? '-',
                $loan->user->identity_number ?? '-',
                $itemsText,
                $loan->loan_date?->format('d/m/Y') ?? '-',
                $loan->due_date?->format('d/m/Y') ?? '-',
                $loan->approvedBy->name ?? '-',
                $loan->status_label,
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Transaksi', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Total Unit Dipinjam', 'value' => number_format($totalUnits, 0, ',', '.') . ' Unit'],
                ['label' => 'Sedang Aktif / Dipinjam', 'value' => number_format($records->whereIn('status', ['approved', 'borrowed', 'overdue'])->count(), 0, ',', '.')],
                ['label' => 'Selesai Dikembalikan', 'value' => number_format($records->where('status', 'returned')->count(), 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildReturnReport(array $meta, array $filters): array
    {
        $query = EquipmentReturn::with(['loan.user', 'returnedBy', 'verifiedBy', 'returnDetails.equipment'])
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('return_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('return_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['category_id'] ?? null), function (Builder $q) use ($filters) {
                $q->whereHas('loan.loanDetails.equipment', fn (Builder $eq) => $eq->where('category_id', $filters['category_id']));
            })
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        $records = $query->get();

        $headings = [
            'No',
            'Kode Pengembalian',
            'Kode Pinjam',
            'Peminjam',
            'Tgl Jatuh Tempo',
            'Tgl Kembali',
            'Terlambat (Hari)',
            'Kondisi Kembali',
            'Nominal Denda',
            'Petugas Pemeriksa',
            'Status',
        ];

        $rows = [];
        foreach ($records as $idx => $ret) {
            $condText = $ret->returnDetails->isNotEmpty()
                ? $ret->returnDetails->map(fn ($d) => ($d->equipment->code ?? '-') . ': ' . $d->condition_label)->implode('; ')
                : 'Belum diperiksa';

            $rows[] = [
                $idx + 1,
                $ret->code,
                $ret->loan->code ?? '-',
                $ret->returnedBy->name ?? '-',
                $ret->loan?->due_date?->format('d/m/Y') ?? '-',
                $ret->return_date?->format('d/m/Y') ?? '-',
                $ret->late_days . ' hari',
                $condText,
                'Rp' . number_format((float) $ret->fine_amount, 0, ',', '.'),
                $ret->verifiedBy->name ?? '-',
                $ret->status_label,
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Pengembalian', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Terverifikasi', 'value' => number_format($records->where('status', 'verified')->count(), 0, ',', '.')],
                ['label' => 'Menunggu Verifikasi', 'value' => number_format($records->where('status', 'pending_verification')->count(), 0, ',', '.')],
                ['label' => 'Total Denda Tercatat', 'value' => 'Rp' . number_format((float) $records->sum('fine_amount'), 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildOverdueReport(array $meta, array $filters): array
    {
        $query = Loan::with(['user', 'loanDetails.equipment', 'equipmentReturn'])
            ->where(function (Builder $q) {
                $q->where('status', 'overdue')
                    ->orWhere(function (Builder $b) {
                        $b->where('status', 'borrowed')
                            ->whereDate('due_date', '<', Carbon::today()->toDateString());
                    })
                    ->orWhereHas('equipmentReturn', fn (Builder $rq) => $rq->where('late_days', '>', 0));
            })
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('due_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('due_date', '<=', $filters['date_to']))
            ->when(filled($filters['category_id'] ?? null), function (Builder $q) use ($filters) {
                $q->whereHas('loanDetails.equipment', fn (Builder $eq) => $eq->where('category_id', $filters['category_id']));
            })
            ->orderByDesc('due_date');

        $records = $query->get();
        $fineRate = \App\Models\Setting::fineRatePerDay();

        $headings = [
            'No',
            'Kode Peminjaman',
            'Peminjam',
            'Telepon',
            'Daftar Alat',
            'Jatuh Tempo',
            'Keterlambatan',
            'Denda (Aktual / Estimasi)',
            'Status Saat Ini',
        ];

        $rows = [];
        $totalFineOrEst = 0;

        foreach ($records as $idx => $loan) {
            $lateDays = $loan->equipmentReturn
                ? (int) $loan->equipmentReturn->late_days
                : $loan->current_late_days;

            $fineVal = $loan->equipmentReturn
                ? (float) $loan->equipmentReturn->fine_amount
                : ($lateDays * $fineRate);

            $totalFineOrEst += $fineVal;

            $rows[] = [
                $idx + 1,
                $loan->code,
                $loan->user->name ?? '-',
                $loan->user->phone ?? '-',
                $loan->loanDetails->map(fn ($d) => ($d->equipment->name ?? '-') . " ({$d->qty})")->implode('; '),
                $loan->due_date?->format('d/m/Y') ?? '-',
                $lateDays . ' hari',
                'Rp' . number_format($fineVal, 0, ',', '.'),
                $loan->status_label,
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Kasus Terlambat', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Belum Dikembalikan (Overdue Aktif)', 'value' => number_format($records->whereIn('status', ['borrowed', 'overdue'])->count(), 0, ',', '.')],
                ['label' => 'Sudah Dikembalikan Terlambat', 'value' => number_format($records->where('status', 'returned')->count(), 0, ',', '.')],
                ['label' => 'Total Denda / Estimasi', 'value' => 'Rp' . number_format($totalFineOrEst, 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildFineReport(array $meta, array $filters): array
    {
        $query = EquipmentReturn::with(['loan.user', 'verifiedBy'])
            ->where('fine_amount', '>', 0)
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('return_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('return_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), function (Builder $q) use ($filters) {
                if ($filters['status'] === 'paid') {
                    $q->where('fine_paid', true);
                } elseif ($filters['status'] === 'unpaid') {
                    $q->where('fine_paid', false);
                }
            })
            ->orderByDesc('return_date');

        $records = $query->get();

        $headings = [
            'No',
            'Kode Pengembalian',
            'Kode Pinjam',
            'Nama Peminjam',
            'Tgl Kembali',
            'Hari Terlambat',
            'Tarif / Hari',
            'Total Denda',
            'Status Pembayaran',
            'Diverifikasi Oleh',
        ];

        $rows = [];
        foreach ($records as $idx => $ret) {
            $rate = (float) $ret->fine_per_day_snapshot > 0
                ? (float) $ret->fine_per_day_snapshot
                : \App\Models\Setting::fineRatePerDay();

            $rows[] = [
                $idx + 1,
                $ret->code,
                $ret->loan->code ?? '-',
                $ret->loan->user->name ?? '-',
                $ret->return_date?->format('d/m/Y') ?? '-',
                $ret->late_days . ' hari',
                'Rp' . number_format($rate, 0, ',', '.'),
                'Rp' . number_format((float) $ret->fine_amount, 0, ',', '.'),
                $ret->fine_paid ? 'Lunas' : 'Belum Dibayar',
                $ret->verifiedBy->name ?? '-',
            ];
        }

        $paidSum = (float) $records->where('fine_paid', true)->sum('fine_amount');
        $unpaidSum = (float) $records->where('fine_paid', false)->sum('fine_amount');

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Transaksi Berdenda', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Total Seluruh Denda', 'value' => 'Rp' . number_format($paidSum + $unpaidSum, 0, ',', '.')],
                ['label' => 'Denda Sudah Dibayar', 'value' => 'Rp' . number_format($paidSum, 0, ',', '.')],
                ['label' => 'Denda Belum Dibayar', 'value' => 'Rp' . number_format($unpaidSum, 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildEquipmentReport(array $meta, array $filters): array
    {
        $query = Equipment::with('category')
            ->when(filled($filters['category_id'] ?? null), fn (Builder $q) => $q->where('category_id', $filters['category_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['condition'] ?? null), fn (Builder $q) => $q->where('condition', $filters['condition']))
            ->orderBy('code');

        $records = $query->get();

        $headings = [
            'No',
            'Kode Alat',
            'Nama Peralatan',
            'Kategori',
            'Lokasi Penyimpanan',
            'Kondisi',
            'Total Stok',
            'Tersedia',
            'Dipinjam',
            'Rusak / Maintenance',
            'Status',
        ];

        $rows = [];
        foreach ($records as $idx => $eq) {
            $rows[] = [
                $idx + 1,
                $eq->code,
                $eq->name,
                $eq->category->name ?? '-',
                $eq->location ?? '-',
                $eq->condition_label,
                $eq->qty_total,
                $eq->qty_available,
                $eq->qty_borrowed,
                $eq->qty_damaged,
                $eq->status_label,
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Jenis Alat', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Total Unit Inventaris', 'value' => number_format($records->sum('qty_total'), 0, ',', '.') . ' Unit'],
                ['label' => 'Unit Siap Dipinjam', 'value' => number_format($records->sum('qty_available'), 0, ',', '.') . ' Unit'],
                ['label' => 'Unit Rusak / Maintenance', 'value' => number_format($records->sum('qty_damaged'), 0, ',', '.') . ' Unit'],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildCategoryReport(array $meta, array $filters): array
    {
        $query = Category::withCount('equipments')
            ->with('equipments')
            ->when(filled($filters['status'] ?? null), function (Builder $q) use ($filters) {
                if ($filters['status'] === 'active') {
                    $q->where('is_active', true);
                } elseif ($filters['status'] === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->orderBy('code');

        $records = $query->get();

        $headings = [
            'No',
            'Kode Kategori',
            'Nama Kategori',
            'Deskripsi',
            'Jumlah Jenis Alat',
            'Total Unit',
            'Unit Tersedia',
            'Status Kategori',
        ];

        $rows = [];
        foreach ($records as $idx => $cat) {
            $rows[] = [
                $idx + 1,
                $cat->code,
                $cat->name,
                $cat->description ?? '-',
                $cat->equipments_count,
                $cat->equipments->sum('qty_total'),
                $cat->equipments->sum('qty_available'),
                $cat->is_active ? 'Aktif' : 'Nonaktif',
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Kategori', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Kategori Aktif', 'value' => number_format($records->where('is_active', true)->count(), 0, ',', '.')],
                ['label' => 'Total Jenis Alat', 'value' => number_format($records->sum('equipments_count'), 0, ',', '.')],
                ['label' => 'Total Unit Terdaftar', 'value' => number_format($records->sum(fn ($c) => $c->equipments->sum('qty_total')), 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    private static function buildUserReport(array $meta, array $filters): array
    {
        $query = User::withCount('loans')
            ->when(filled($filters['role'] ?? null), fn (Builder $q) => $q->where('role', $filters['role']))
            ->when(filled($filters['status'] ?? null), function (Builder $q) use ($filters) {
                if ($filters['status'] === 'active') {
                    $q->where('is_active', true);
                } elseif ($filters['status'] === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->orderBy('role')
            ->orderBy('name');

        $records = $query->get();

        $headings = [
            'No',
            'Nama Lengkap',
            'Username',
            'Email',
            'No. Identitas (NIS/NIP)',
            'Role',
            'No. Telepon',
            'Total Pinjaman',
            'Status Akun',
        ];

        $rows = [];
        foreach ($records as $idx => $u) {
            $rows[] = [
                $idx + 1,
                $u->name,
                $u->username,
                $u->email,
                $u->identity_number ?? '-',
                $u->role_label,
                $u->phone ?? '-',
                $u->loans_count,
                $u->is_active ? 'Aktif' : 'Nonaktif',
            ];
        }

        return [
            'meta' => $meta,
            'records' => $records,
            'summary' => [
                ['label' => 'Total Pengguna', 'value' => number_format($records->count(), 0, ',', '.')],
                ['label' => 'Administrator', 'value' => number_format($records->where('role', 'admin')->count(), 0, ',', '.')],
                ['label' => 'Petugas Lab', 'value' => number_format($records->where('role', 'petugas')->count(), 0, ',', '.')],
                ['label' => 'Peminjam', 'value' => number_format($records->where('role', 'peminjam')->count(), 0, ',', '.')],
            ],
            'headings' => $headings,
            'rows' => $rows,
        ];
    }
}
