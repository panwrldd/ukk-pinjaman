<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $module = $request->string('module')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $logs = ActivityLog::with('user')
            ->search($search)
            ->when(filled($module), fn (Builder $q) => $q->where('module', $module))
            ->when(filled($dateFrom), fn (Builder $q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when(filled($dateTo), fn (Builder $q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $modules = ActivityLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('admin.activity-logs.index', compact(
            'logs',
            'modules',
            'search',
            'module',
            'dateFrom',
            'dateTo'
        ));
    }
}
