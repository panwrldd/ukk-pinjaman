<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Record a structured activity log entry.
     */
    public static function log(
        string $action,
        string $description = '',
        string $module = 'system',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): void {
        try {
            ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'module' => $module,
                'action' => $action,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            logger()->error('ActivityLogService failed: ' . $e->getMessage());
        }
    }
}
