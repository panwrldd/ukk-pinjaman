<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loans';

    protected $fillable = [
        'code',
        'user_id',
        'loan_date',
        'due_date',
        'purpose',
        'status',
        'approved_by',
        'approved_at',
        'handed_over_by',
        'handed_over_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'handed_over_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function loanDetails(): HasMany
    {
        return $this->hasMany(LoanDetail::class, 'loan_id');
    }

    public function equipmentReturn(): HasOne
    {
        return $this->hasOne(EquipmentReturn::class, 'loan_id');
    }

    public function return(): HasOne
    {
        return $this->hasOne(EquipmentReturn::class, 'loan_id');
    }

    public function isLate(): bool
    {
        if ($this->status === 'overdue') {
            return true;
        }

        if ($this->status !== 'borrowed' || !$this->due_date) {
            return false;
        }

        return Carbon::today()->gt($this->due_date->copy()->startOfDay());
    }

    public function getCurrentLateDaysAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        if ($this->status === 'returned' && $this->equipmentReturn) {
            return (int) $this->equipmentReturn->late_days;
        }

        if (!in_array($this->status, ['borrowed', 'overdue'], true)) {
            return 0;
        }

        $today = Carbon::today();
        $due = $this->due_date->copy()->startOfDay();

        return $today->gt($due) ? (int) $due->diffInDays($today) : 0;
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'borrowed' && $this->isLate()) {
            return 'overdue';
        }

        return $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui (Siap Diambil)',
            'rejected' => 'Ditolak',
            'borrowed' => 'Sedang Dipinjam',
            'overdue' => 'Terlambat (Overdue)',
            'returned' => 'Dikembalikan',
            'cancelled' => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->effective_status) {
            'pending' => 'amber',
            'approved' => 'blue',
            'rejected' => 'red',
            'borrowed' => 'indigo',
            'overdue' => 'red',
            'returned' => 'emerald',
            'cancelled' => 'slate',
            default => 'slate',
        };
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'borrowed', 'overdue']);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->whereIn('status', ['borrowed', 'overdue']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'overdue')
                ->orWhere(function (Builder $sub) {
                    $sub->where('status', 'borrowed')
                        ->whereDate('due_date', '<', Carbon::today()->toDateString());
                });
        });
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('purpose', 'like', "%{$term}%")
                ->orWhereHas('user', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%")
                        ->orWhere('identity_number', 'like', "%{$term}%");
                });
        });
    }
}
