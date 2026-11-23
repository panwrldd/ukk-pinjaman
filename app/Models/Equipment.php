<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Equipment extends Model
{
    use HasFactory;

    /**
     * Explicit table name because "equipment" is uncountable in English inflector.
     */
    protected $table = 'equipments';

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'photo',
        'condition',
        'qty_total',
        'qty_available',
        'qty_damaged',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'qty_total' => 'integer',
        'qty_available' => 'integer',
        'qty_damaged' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function loanDetails(): HasMany
    {
        return $this->hasMany(LoanDetail::class, 'equipment_id');
    }

    public function returnDetails(): HasMany
    {
        return $this->hasMany(ReturnDetail::class, 'equipment_id');
    }

    public function getQtyBorrowedAttribute(): int
    {
        return max(0, $this->qty_total - $this->qty_available - $this->qty_damaged);
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
            default => ucfirst((string) $this->condition),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'tersedia' => 'Tersedia',
            'tidak_tersedia' => 'Stok Habis / Tidak Tersedia',
            'maintenance' => 'Dalam Perbaikan (Maintenance)',
            default => ucfirst((string) $this->status),
        };
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            return asset('storage/' . $this->photo);
        }

        return null;
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'tersedia')
            ->where('qty_available', '>', 0)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
