<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        if ($user->isAdmin() || $user->isPetugas()) {
            return true;
        }

        return $loan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isPeminjam() && $user->is_active;
    }

    public function cancel(User $user, Loan $loan): bool
    {
        if ($user->isAdmin() || $user->isPetugas()) {
            return in_array($loan->status, ['pending', 'approved'], true);
        }

        return $loan->user_id === $user->id && $loan->status === 'pending';
    }

    public function approve(User $user, Loan $loan): bool
    {
        return ($user->isAdmin() || $user->isPetugas()) && $loan->status === 'pending';
    }

    public function reject(User $user, Loan $loan): bool
    {
        return ($user->isAdmin() || $user->isPetugas()) && $loan->status === 'pending';
    }

    public function handOver(User $user, Loan $loan): bool
    {
        return ($user->isAdmin() || $user->isPetugas()) && $loan->status === 'approved';
    }
}
