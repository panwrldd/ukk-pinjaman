<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\LoanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        LoanService::syncOverdueStatuses();
        $userId = (int) Auth::id();

        $summary = [
            'active_borrowed' => Loan::where('user_id', $userId)->whereIn('status', ['borrowed', 'overdue'])->count(),
            'pending_requests' => Loan::where('user_id', $userId)->where('status', 'pending')->count(),
            'approved_ready' => Loan::where('user_id', $userId)->where('status', 'approved')->count(),
            'completed_loans' => Loan::where('user_id', $userId)->where('status', 'returned')->count(),
            'overdue_count' => Loan::where('user_id', $userId)->overdue()->count(),
            'fine_rate' => Setting::fineRatePerDay(),
        ];

        $activeSchedule = Loan::with(['loanDetails.equipment', 'equipmentReturn'])
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'borrowed', 'overdue'])
            ->orderBy('due_date')
            ->get();

        $recentHistory = Loan::with(['loanDetails.equipment', 'equipmentReturn'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $featuredEquipments = Equipment::with('category')
            ->available()
            ->orderByDesc('qty_available')
            ->limit(6)
            ->get();

        return view('borrower.dashboard', compact(
            'summary',
            'activeSchedule',
            'recentHistory',
            'featuredEquipments'
        ));
    }
}
