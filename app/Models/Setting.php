<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting !== null ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, ?int $updatedBy = null, ?string $type = null, ?string $description = null): void
    {
        $payload = ['value' => (string) $value];

        if ($updatedBy !== null) {
            $payload['updated_by'] = $updatedBy;
        }
        if ($type !== null) {
            $payload['type'] = $type;
        }
        if ($description !== null) {
            $payload['description'] = $description;
        }

        static::updateOrCreate(['key' => $key], $payload);
        Cache::forget("setting.{$key}");
    }

    public static function fineRatePerDay(): int
    {
        return max(0, (int) static::get('fine_rate_per_day', 5000));
    }
}
