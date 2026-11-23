<?php

namespace App\Services;

use App\Models\Equipment;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EquipmentService
{
    public static function createEquipment(array $data, ?UploadedFile $photo, int $actorId): Equipment
    {
        DB::beginTransaction();

        try {
            $qtyTotal = (int) $data['qty_total'];
            $qtyDamaged = isset($data['qty_damaged']) ? (int) $data['qty_damaged'] : 0;
            $qtyAvailable = isset($data['qty_available'])
                ? (int) $data['qty_available']
                : max(0, $qtyTotal - $qtyDamaged);

            if (($qtyAvailable + $qtyDamaged) > $qtyTotal) {
                throw new DomainException('Jumlah stok tersedia dan rusak tidak boleh melebihi total stok.');
            }

            $photoPath = null;
            if ($photo) {
                $photoPath = $photo->store('equipments', 'public');
            }

            $status = $data['status'];
            if ($qtyAvailable === 0 && $status === 'tersedia') {
                $status = $qtyDamaged > 0 ? 'maintenance' : 'tidak_tersedia';
            }

            $equipment = Equipment::create([
                'category_id' => $data['category_id'],
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'photo' => $photoPath,
                'condition' => $data['condition'],
                'qty_total' => $qtyTotal,
                'qty_available' => $qtyAvailable,
                'qty_damaged' => $qtyDamaged,
                'location' => $data['location'] ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogService::log(
                action: 'equipment_created',
                description: "Menambahkan peralatan baru: {$equipment->name} ({$equipment->code}) dengan stok {$equipment->qty_total} unit.",
                module: 'equipments',
                referenceType: 'equipments',
                referenceId: $equipment->id,
                newValues: $equipment->only(['code', 'name', 'qty_total', 'qty_available', 'condition', 'status']),
                userId: $actorId
            );

            DB::commit();

            return $equipment;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function updateEquipment(
        Equipment $equipment,
        array $data,
        ?UploadedFile $photo,
        bool $removePhoto,
        int $actorId
    ): Equipment {
        DB::beginTransaction();

        try {
            $locked = Equipment::lockForUpdate()->findOrFail($equipment->id);
            $oldSnapshot = $locked->only(['code', 'name', 'qty_total', 'qty_available', 'qty_damaged', 'condition', 'status']);

            $currentlyBorrowed = $locked->qty_borrowed;
            $newTotal = (int) $data['qty_total'];
            $newDamaged = isset($data['qty_damaged']) ? (int) $data['qty_damaged'] : $locked->qty_damaged;

            if ($newTotal < ($currentlyBorrowed + $newDamaged)) {
                throw new DomainException(
                    "Total stok tidak dapat dikurangi di bawah jumlah unit yang sedang dipinjam ({$currentlyBorrowed}) ditambah unit rusak ({$newDamaged})."
                );
            }

            $newAvailable = $newTotal - $currentlyBorrowed - $newDamaged;

            $photoPath = $locked->photo;
            if ($removePhoto && $photoPath) {
                Storage::disk('public')->delete($photoPath);
                $photoPath = null;
            }
            if ($photo) {
                if ($photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $photo->store('equipments', 'public');
            }

            $status = $data['status'];
            if ($newAvailable === 0 && $status === 'tersedia') {
                $status = $newDamaged > 0 ? 'maintenance' : 'tidak_tersedia';
            }

            $locked->update([
                'category_id' => $data['category_id'],
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'photo' => $photoPath,
                'condition' => $data['condition'],
                'qty_total' => $newTotal,
                'qty_available' => $newAvailable,
                'qty_damaged' => $newDamaged,
                'location' => $data['location'] ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogService::log(
                action: 'equipment_updated',
                description: "Memperbarui data peralatan: {$locked->name} ({$locked->code}).",
                module: 'equipments',
                referenceType: 'equipments',
                referenceId: $locked->id,
                oldValues: $oldSnapshot,
                newValues: $locked->only(['code', 'name', 'qty_total', 'qty_available', 'qty_damaged', 'condition', 'status']),
                userId: $actorId
            );

            DB::commit();

            return $locked->fresh('category');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore repaired equipment units from qty_damaged back to qty_available.
     */
    public static function restoreDamagedUnits(Equipment $equipment, int $qtyToRestore, int $actorId, ?string $notes = null): Equipment
    {
        DB::beginTransaction();

        try {
            $locked = Equipment::lockForUpdate()->findOrFail($equipment->id);

            if ($qtyToRestore <= 0 || $qtyToRestore > $locked->qty_damaged) {
                throw new DomainException('Jumlah unit yang dipulihkan melebihi jumlah unit dalam perbaikan.');
            }

            $newDamaged = $locked->qty_damaged - $qtyToRestore;
            $newAvailable = $locked->qty_available + $qtyToRestore;

            $locked->update([
                'qty_damaged' => $newDamaged,
                'qty_available' => $newAvailable,
                'condition' => $newDamaged === 0 ? 'baik' : $locked->condition,
                'status' => $newAvailable > 0 ? 'tersedia' : 'tidak_tersedia',
                'notes' => $notes ? trim($notes) : $locked->notes,
            ]);

            ActivityLogService::log(
                action: 'equipment_restored',
                description: "Memulihkan {$qtyToRestore} unit {$locked->name} ({$locked->code}) dari status rusak/maintenance ke stok tersedia.",
                module: 'equipments',
                referenceType: 'equipments',
                referenceId: $locked->id,
                userId: $actorId
            );

            DB::commit();

            return $locked->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function deleteEquipment(Equipment $equipment, int $actorId): void
    {
        DB::beginTransaction();

        try {
            $locked = Equipment::lockForUpdate()->findOrFail($equipment->id);

            if ($locked->loanDetails()->exists()) {
                throw new DomainException(
                    'Peralatan ini tidak dapat dihapus karena sudah tercatat dalam riwayat transaksi peminjaman. Ubah statusnya menjadi Tidak Tersedia.'
                );
            }

            $code = $locked->code;
            $name = $locked->name;
            $photo = $locked->photo;
            $id = $locked->id;

            $locked->delete();

            if ($photo) {
                Storage::disk('public')->delete($photo);
            }

            ActivityLogService::log(
                action: 'equipment_deleted',
                description: "Menghapus peralatan: {$name} ({$code}).",
                module: 'equipments',
                referenceType: 'equipments',
                referenceId: $id,
                userId: $actorId
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
