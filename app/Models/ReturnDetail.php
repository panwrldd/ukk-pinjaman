<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnDetail extends Model
{
    use HasFactory;

    protected $table = 'return_details';

    protected $fillable = [
        'return_id',
        'loan_detail_id',
        'equipment_id',
        'qty_returned',
        'qty_damaged',
        'condition_after',
        'damage_notes',
    ];

    protected $casts = [
        'qty_returned' => 'integer',
        'qty_damaged' => 'integer',
    ];

    public function equipmentReturn(): BelongsTo
    {
        return $this->belongsTo(EquipmentReturn::class, 'return_id');
    }

    public function loanDetail(): BelongsTo
    {
        return $this->belongsTo(LoanDetail::class, 'loan_detail_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition_after) {
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
            default => ucfirst((string) $this->condition_after),
        };
    }
}
