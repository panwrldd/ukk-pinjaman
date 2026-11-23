<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentReturn extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'code',
        'loan_id',
        'returned_by',
        'verified_by',
        'return_date',
        'verified_at',
        'late_days',
        'fine_per_day_snapshot',
        'fine_amount',
        'fine_paid',
        'status',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'verified_at' => 'datetime',
        'late_days' => 'integer',
        'fine_per_day_snapshot' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function returnDetails(): HasMany
    {
        return $this->hasMany(ReturnDetail::class, 'return_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_verification' => 'Menunggu Verifikasi Petugas',
            'verified' => 'Terverifikasi Selesai',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending_verification' => 'amber',
            'verified' => 'emerald',
            default => 'slate',
        };
    }

    public function getFormattedFineAttribute(): string
    {
        return 'Rp' . number_format((float) $this->fine_amount, 0, ',', '.');
    }

    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('status', 'pending_verification');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhereHas('loan', fn (Builder $lq) => $lq->where('code', 'like', "%{$term}%"))
                ->orWhereHas('returnedBy', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%");
                });
        });
    }
}
