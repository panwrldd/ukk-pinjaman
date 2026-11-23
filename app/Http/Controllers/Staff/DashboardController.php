<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        LoanService::syncOverdueStatuses();

        $metrics = [
            'pending_approvals' => Loan::where('status', 'pending')->count(),
            'ready_for_handover' => Loan::where('status', 'approved')->count(),
            'currently_borrowed' => Loan::whereIn('status', ['borrowed', 'overdue'])->count(),
            'overdue_count' => Loan::overdue()->count(),
            'pending_returns' => EquipmentReturn::where('status', 'pending_verification')->count(),
            'damaged_units' => (int) Equipment::sum('qty_damaged'),
        ];

        $pendingLoans = Loan::with(['user', 'loanDetails.equipment'])
            ->where('status', 'pending')
            ->orderBy('loan_date')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $readyHandoverLoans = Loan::with(['user', 'loanDetails.equipment'])
            ->where('status', 'approved')
            ->orderBy('loan_date')
            ->limit(6)
            ->get();

        $pendingReturns = EquipmentReturn::with(['loan.user', 'loan.loanDetails.equipment', 'returnedBy'])
            ->where('status', 'pending_verification')
            ->orderBy('return_date')
            ->limit(6)
            ->get();

        $overdueLoans = Loan::with(['user', 'loanDetails.equipment'])
            ->overdue()
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->whereIn('module', ['loans', 'returns', 'db_trigger'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('staff.dashboard', compact(
            'metrics',
            'pendingLoans',
            'readyHandoverLoans',
            'pendingReturns',
            'overdueLoans',
            'recentActivities'
        ));
    }
}
