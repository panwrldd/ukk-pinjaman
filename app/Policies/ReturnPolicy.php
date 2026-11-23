<?php

namespace App\Policies;

use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Models\User;

class ReturnPolicy
{
    public function view(User $user, EquipmentReturn $equipmentReturn): bool
    {
        if ($user->isAdmin() || $user->isPetugas()) {
            return true;
        }

        return $equipmentReturn->returned_by === $user->id
            || ($equipmentReturn->loan && $equipmentReturn->loan->user_id === $user->id);
    }

    public function submit(User $user, Loan $loan): bool
    {
        if (!in_array($loan->status, ['borrowed', 'overdue'], true)) {
            return false;
        }

        if ($loan->equipmentReturn()->exists()) {
            return false;
        }

        if ($user->isAdmin() || $user->isPetugas()) {
            return true;
        }

        return $loan->user_id === $user->id;
    }

    public function verify(User $user, EquipmentReturn $equipmentReturn): bool
    {
        return ($user->isAdmin() || $user->isPetugas())
            && $equipmentReturn->status === 'pending_verification';
    }
}
