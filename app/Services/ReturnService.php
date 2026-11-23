<?php

namespace App\Services;

use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Models\ReturnDetail;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    /**
     * Generate a readable unique return code: RTN-YYYYMMDD-XXXX.
     */
    public static function generateCode(): string
    {
        $datePrefix = 'RTN-' . now()->format('Ymd') . '-';
        $lastReturn = EquipmentReturn::where('code', 'like', $datePrefix . '%')
            ->orderByDesc('id')
            ->first();

        $nextSequence = 1;
        if ($lastReturn && preg_match('/-(\d{4})$/', $lastReturn->code, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Preview late days & fine calculation directly via MariaDB Stored Functions.
     *
     * @return array{late_days: int, fine_amount: float}
     */
    public static function previewFine(string $dueDate, ?string $returnDate = null): array
    {
        $retDate = $returnDate ? Carbon::parse($returnDate)->toDateString() : Carbon::today()->toDateString();
        $due = Carbon::parse($dueDate)->toDateString();

        $row = DB::selectOne(
            'SELECT fn_count_late_days(?, ?) AS late_days, fn_calculate_fine(?, ?) AS fine_amount',
            [$due, $retDate, $due, $retDate]
        );

        return [
            'late_days' => (int) ($row->late_days ?? 0),
            'fine_amount' => (float) ($row->fine_amount ?? 0),
        ];
    }

    /**
     * Submit a return request by borrower (or initiated by staff on desk handover).
     */
    public static function submitReturn(int $loanId, int $actorId, ?string $notes = null, ?string $returnDate = null): EquipmentReturn
    {
        DB::beginTransaction();

        try {
            $loan = Loan::with('equipmentReturn')->lockForUpdate()->findOrFail($loanId);

            if (!in_array($loan->status, ['borrowed', 'overdue'], true)) {
                throw new DomainException('Hanya peminjaman aktif yang sedang dipinjam yang dapat diajukan pengembaliannya.');
            }

            if ($loan->equipmentReturn) {
                throw new DomainException('Pengajuan pengembalian untuk transaksi ini sudah pernah dibuat.');
            }

            $actualReturnDate = $returnDate
                ? Carbon::parse($returnDate)->toDateString()
                : Carbon::today()->toDateString();

            $estimate = static::previewFine($loan->due_date->toDateString(), $actualReturnDate);

            $equipmentReturn = EquipmentReturn::create([
                'code' => static::generateCode(),
                'loan_id' => $loan->id,
                'returned_by' => $actorId,
                'return_date' => $actualReturnDate,
                'late_days' => $estimate['late_days'],
                'fine_amount' => $estimate['fine_amount'],
                'status' => 'pending_verification',
                'notes' => $notes ? trim($notes) : null,
            ]);

            ActivityLogService::log(
                action: 'return_submitted',
                description: "Pengajuan pengembalian {$equipmentReturn->code} untuk pinjaman {$loan->code} dikirim.",
                module: 'returns',
                referenceType: 'returns',
                referenceId: $equipmentReturn->id,
                userId: $actorId
            );

            DB::commit();

            return $equipmentReturn->load(['loan.loanDetails.equipment', 'returnedBy']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify physical equipment condition, compute fine, and update stock via sp_process_return.
     *
     * @param  array<int, array{loan_detail_id: int, equipment_id?: int, qty_returned?: int, qty_damaged?: int, condition_after: string, damage_notes?: ?string}>  $details
     */
    public static function verifyReturn(
        int $returnId,
        int $staffId,
        array $details,
        bool $finePaid = false,
        ?string $notes = null
    ): EquipmentReturn {
        DB::beginTransaction();

        try {
            $equipmentReturn = EquipmentReturn::with('loan.loanDetails')
                ->lockForUpdate()
                ->findOrFail($returnId);

            if ($equipmentReturn->status !== 'pending_verification') {
                throw new DomainException('Data pengembalian ini sudah selesai diverifikasi.');
            }

            $loanDetailsById = $equipmentReturn->loan->loanDetails->keyBy('id');

            if (count($details) !== $loanDetailsById->count()) {
                throw new DomainException('Seluruh item peralatan yang dipinjam wajib diperiksa kondisinya.');
            }

            // Clear any draft details if present before inserting verified inspection rows
            ReturnDetail::where('return_id', $equipmentReturn->id)->delete();

            foreach ($details as $detail) {
                $loanDetailId = (int) $detail['loan_detail_id'];
                $loanDetail = $loanDetailsById->get($loanDetailId);

                if (!$loanDetail) {
                    throw new DomainException('Item detail peminjaman tidak sesuai dengan transaksi ini.');
                }

                $qtyReturned = isset($detail['qty_returned']) ? (int) $detail['qty_returned'] : $loanDetail->qty;
                if ($qtyReturned !== $loanDetail->qty) {
                    throw new DomainException('Jumlah unit yang dikembalikan harus sesuai dengan jumlah yang dipinjam.');
                }

                $conditionAfter = $detail['condition_after'];
                if (!in_array($conditionAfter, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
                    throw new DomainException('Kondisi pengembalian tidak valid.');
                }

                // Determine damaged quantity: if condition is rusak_berat and qty_damaged not given, all returned units of this row are damaged
                $qtyDamaged = isset($detail['qty_damaged'])
                    ? (int) $detail['qty_damaged']
                    : ($conditionAfter === 'rusak_berat' ? $qtyReturned : 0);

                if ($conditionAfter === 'rusak_berat' && $qtyDamaged <= 0) {
                    $qtyDamaged = $qtyReturned;
                }
                if ($conditionAfter !== 'rusak_berat') {
                    $qtyDamaged = 0;
                }

                if ($qtyDamaged < 0 || $qtyDamaged > $qtyReturned) {
                    throw new DomainException('Jumlah unit rusak berat tidak valid.');
                }

                ReturnDetail::create([
                    'return_id' => $equipmentReturn->id,
                    'loan_detail_id' => $loanDetail->id,
                    'equipment_id' => $loanDetail->equipment_id,
                    'qty_returned' => $qtyReturned,
                    'qty_damaged' => $qtyDamaged,
                    'condition_after' => $conditionAfter,
                    'damage_notes' => $detail['damage_notes'] ?? null,
                ]);
            }

            // Delegate stock restoration, damaged stock separation, & fine computation strictly to Stored Procedure
            DB::statement(
                'CALL sp_process_return(?, ?, ?, ?)',
                [$equipmentReturn->id, $staffId, $finePaid ? 1 : 0, $notes]
            );

            $verifiedReturn = $equipmentReturn->fresh(['loan', 'returnDetails.equipment', 'verifiedBy']);

            ActivityLogService::log(
                action: 'return_verified',
                description: sprintf(
                    'Pengembalian %s diverifikasi. Keterlambatan: %d hari, Denda: Rp%s.',
                    $verifiedReturn->code,
                    $verifiedReturn->late_days,
                    number_format((float) $verifiedReturn->fine_amount, 0, ',', '.')
                ),
                module: 'returns',
                referenceType: 'returns',
                referenceId: $verifiedReturn->id,
                userId: $staffId
            );

            DB::commit();

            return $verifiedReturn;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
