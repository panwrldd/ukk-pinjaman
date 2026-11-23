<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Setting;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Generate a readable unique transaction code: LN-YYYYMMDD-XXXX.
     */
    public static function generateCode(): string
    {
        $datePrefix = 'LN-' . now()->format('Ymd') . '-';
        $lastLoan = Loan::where('code', 'like', $datePrefix . '%')
            ->orderByDesc('id')
            ->first();

        $nextSequence = 1;
        if ($lastLoan && preg_match('/-(\d{4})$/', $lastLoan->code, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new loan request inside an explicit DB transaction (BEGIN / COMMIT / ROLLBACK).
     *
     * @param  array{loan_date: string, due_date: string, purpose: string, notes?: ?string, items: array<int, array{equipment_id: int, qty: int}>}  $data
     */
    public static function createLoan(array $data, int $userId): Loan
    {
        DB::beginTransaction();

        try {
            $loanDate = Carbon::parse($data['loan_date'])->startOfDay();
            $dueDate = Carbon::parse($data['due_date'])->startOfDay();

            if ($dueDate->lt($loanDate)) {
                throw new DomainException('Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman.');
            }

            $maxLoanDays = (int) Setting::get('max_loan_days', 7);
            if ($maxLoanDays > 0 && $loanDate->diffInDays($dueDate) > $maxLoanDays) {
                throw new DomainException("Durasi peminjaman maksimal adalah {$maxLoanDays} hari.");
            }

            $maxActiveLoans = (int) Setting::get('max_active_loans', 3);
            $activeCount = Loan::where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved', 'borrowed', 'overdue'])
                ->count();

            if ($maxActiveLoans > 0 && $activeCount >= $maxActiveLoans) {
                throw new DomainException("Anda telah mencapai batas maksimal {$maxActiveLoans} transaksi peminjaman aktif.");
            }

            if (empty($data['items'])) {
                throw new DomainException('Pilih minimal satu peralatan untuk dipinjam.');
            }

            // Consolidate duplicate equipment IDs if sent in payload
            $normalizedItems = [];
            foreach ($data['items'] as $item) {
                $eqId = (int) $item['equipment_id'];
                $qty = (int) $item['qty'];
                if ($qty <= 0) {
                    throw new DomainException('Jumlah peminjaman setiap alat minimal 1 unit.');
                }
                $normalizedItems[$eqId] = ($normalizedItems[$eqId] ?? 0) + $qty;
            }

            $equipmentMap = [];
            foreach ($normalizedItems as $eqId => $qty) {
                // Prevent duplicate pending submission for the same equipment by the same borrower
                $hasPendingSameItem = LoanDetail::where('equipment_id', $eqId)
                    ->whereHas('loan', function ($q) use ($userId) {
                        $q->where('user_id', $userId)->where('status', 'pending');
                    })
                    ->exists();

                if ($hasPendingSameItem) {
                    $eqName = Equipment::whereKey($eqId)->value('name') ?? "ID {$eqId}";
                    throw new DomainException("Anda masih memiliki pengajuan berstatus menunggu untuk alat \"{$eqName}\".");
                }

                $equipment = Equipment::with('category')->lockForUpdate()->find($eqId);
                if (!$equipment) {
                    throw new DomainException('Data peralatan tidak ditemukan.');
                }
                if (!$equipment->category || !$equipment->category->is_active) {
                    throw new DomainException("Kategori untuk alat \"{$equipment->name}\" sedang dinonaktifkan.");
                }
                if ($equipment->status !== 'tersedia') {
                    throw new DomainException("Peralatan \"{$equipment->name}\" sedang tidak tersedia untuk dipinjam.");
                }
                if ($equipment->condition === 'rusak_berat') {
                    throw new DomainException("Peralatan \"{$equipment->name}\" dalam kondisi rusak berat.");
                }
                if ($equipment->qty_available < $qty) {
                    throw new DomainException(
                        "Stok \"{$equipment->name}\" tidak mencukupi (tersedia: {$equipment->qty_available}, diminta: {$qty})."
                    );
                }

                $equipmentMap[$eqId] = $equipment;
            }

            $loan = Loan::create([
                'code' => static::generateCode(),
                'user_id' => $userId,
                'loan_date' => $loanDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'purpose' => trim($data['purpose']),
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($normalizedItems as $eqId => $qty) {
                $equipment = $equipmentMap[$eqId];
                LoanDetail::create([
                    'loan_id' => $loan->id,
                    'equipment_id' => $eqId,
                    'qty' => $qty,
                    'condition_before' => $equipment->condition === 'rusak_ringan' ? 'rusak_ringan' : 'baik',
                ]);
            }

            ActivityLogService::log(
                action: 'loan_created',
                description: "Pengajuan peminjaman {$loan->code} dibuat dengan " . count($normalizedItems) . ' jenis alat.',
                module: 'loans',
                referenceType: 'loans',
                referenceId: $loan->id,
                userId: $userId
            );

            DB::commit();

            return $loan->load(['loanDetails.equipment', 'user']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a pending loan via Stored Procedure sp_approve_loan.
     * Stock deduction is executed strictly once inside sp_approve_loan.
     */
    public static function approveLoan(int $loanId, int $staffId, ?string $notes = null): Loan
    {
        DB::beginTransaction();

        try {
            DB::statement('CALL sp_approve_loan(?, ?)', [$loanId, $staffId]);

            $loan = Loan::findOrFail($loanId);
            if ($notes !== null && trim($notes) !== '') {
                $loan->update(['notes' => trim($notes)]);
            }

            ActivityLogService::log(
                action: 'loan_approved',
                description: "Pengajuan peminjaman {$loan->code} disetujui dan stok direservasi.",
                module: 'loans',
                referenceType: 'loans',
                referenceId: $loan->id,
                userId: $staffId
            );

            DB::commit();

            return $loan->fresh(['loanDetails.equipment', 'user', 'approvedBy']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a pending loan with a mandatory reason.
     */
    public static function rejectLoan(int $loanId, int $staffId, string $reason): Loan
    {
        DB::beginTransaction();

        try {
            $cleanReason = trim($reason);
            if ($cleanReason === '') {
                throw new DomainException('Alasan penolakan wajib diisi.');
            }

            $loan = Loan::lockForUpdate()->findOrFail($loanId);
            if ($loan->status !== 'pending') {
                throw new DomainException('Hanya pengajuan berstatus menunggu yang dapat ditolak.');
            }

            $loan->update([
                'status' => 'rejected',
                'approved_by' => $staffId,
                'approved_at' => now(),
                'rejection_reason' => $cleanReason,
            ]);

            ActivityLogService::log(
                action: 'loan_rejected',
                description: "Pengajuan peminjaman {$loan->code} ditolak. Alasan: {$cleanReason}",
                module: 'loans',
                referenceType: 'loans',
                referenceId: $loan->id,
                userId: $staffId
            );

            DB::commit();

            return $loan->fresh(['user', 'approvedBy']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record physical handover of approved equipment to the borrower.
     */
    public static function handOverLoan(int $loanId, int $staffId, ?string $notes = null): Loan
    {
        DB::beginTransaction();

        try {
            $loan = Loan::lockForUpdate()->findOrFail($loanId);
            if ($loan->status !== 'approved') {
                throw new DomainException('Hanya peminjaman berstatus disetujui yang dapat diserahkan alatnya.');
            }

            $payload = [
                'status' => 'borrowed',
                'handed_over_by' => $staffId,
                'handed_over_at' => now(),
            ];

            if ($notes !== null && trim($notes) !== '') {
                $payload['notes'] = trim($notes);
            }

            $loan->update($payload);

            ActivityLogService::log(
                action: 'loan_handed_over',
                description: "Peralatan untuk peminjaman {$loan->code} telah diserahkan kepada peminjam.",
                module: 'loans',
                referenceType: 'loans',
                referenceId: $loan->id,
                userId: $staffId
            );

            DB::commit();

            return $loan->fresh(['loanDetails.equipment', 'user', 'handedOverBy']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a loan safely without leaking stock.
     * Borrowers can only cancel 'pending' loans; Staff/Admin can also cancel 'approved' (uncollected) loans and restore reserved stock.
     */
    public static function cancelLoan(int $loanId, int $actorId, bool $isStaffOrAdmin = false): Loan
    {
        DB::beginTransaction();

        try {
            $loan = Loan::with('loanDetails')->lockForUpdate()->findOrFail($loanId);

            if (!$isStaffOrAdmin && $loan->user_id !== $actorId) {
                throw new DomainException('Anda tidak berhak membatalkan pengajuan milik pengguna lain.');
            }

            if ($loan->status === 'pending') {
                $loan->update(['status' => 'cancelled']);
            } elseif ($isStaffOrAdmin && $loan->status === 'approved') {
                // Restore reserved stock because equipment was never physically handed over
                foreach ($loan->loanDetails as $detail) {
                    $equipment = Equipment::lockForUpdate()->findOrFail($detail->equipment_id);
                    $newAvail = $equipment->qty_available + $detail->qty;
                    $equipment->update([
                        'qty_available' => $newAvail,
                        'status' => $newAvail > 0 ? 'tersedia' : ($equipment->qty_damaged > 0 ? 'maintenance' : 'tidak_tersedia'),
                    ]);
                }
                $loan->update(['status' => 'cancelled']);
            } else {
                throw new DomainException('Peminjaman dengan status saat ini tidak dapat dibatalkan.');
            }

            ActivityLogService::log(
                action: 'loan_cancelled',
                description: "Peminjaman {$loan->code} dibatalkan.",
                module: 'loans',
                referenceType: 'loans',
                referenceId: $loan->id,
                userId: $actorId
            );

            DB::commit();

            return $loan->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Synchronize borrowed loans past due_date into 'overdue' status.
     */
    public static function syncOverdueStatuses(): int
    {
        return Loan::where('status', 'borrowed')
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);
    }
}
