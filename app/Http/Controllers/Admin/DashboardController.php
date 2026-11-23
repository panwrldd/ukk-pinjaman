<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        LoanService::syncOverdueStatuses();

        $stats = [
            'total_users' => User::count(),
            'active_borrowers' => User::where('role', 'peminjam')->where('is_active', true)->count(),
            'total_equipments' => Equipment::count(),
            'total_units' => (int) Equipment::sum('qty_total'),
            'available_units' => (int) Equipment::sum('qty_available'),
            'damaged_units' => (int) Equipment::sum('qty_damaged'),
            'total_categories' => Category::count(),
            'active_loans' => Loan::whereIn('status', ['approved', 'borrowed', 'overdue'])->count(),
            'pending_loans' => Loan::where('status', 'pending')->count(),
            'overdue_loans' => Loan::overdue()->count(),
            'pending_returns' => EquipmentReturn::where('status', 'pending_verification')->count(),
            'total_fines_collected' => (float) EquipmentReturn::where('fine_paid', true)->sum('fine_amount'),
            'total_fines_unpaid' => (float) EquipmentReturn::where('fine_paid', false)->sum('fine_amount'),
            'fine_rate_per_day' => Setting::fineRatePerDay(),
        ];

        // Dynamic chart 1: Last 6 months loan volume from database
        $startMonth = Carbon::now()->startOfMonth()->subMonths(5);
        $rawMonthly = Loan::selectRaw("DATE_FORMAT(loan_date, '%Y-%m') as ym, COUNT(*) as total")
            ->whereDate('loan_date', '>=', $startMonth->toDateString())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyTrend = [];
        for ($i = 0; $i < 6; $i++) {
            $monthPoint = $startMonth->copy()->addMonths($i);
            $key = $monthPoint->format('Y-m');
            $monthlyTrend[] = [
                'label' => $monthPoint->translatedFormat('M Y'),
                'count' => (int) ($rawMonthly[$key] ?? 0),
            ];
        }

        // Dynamic chart 2: Loan item distribution by category from database
        $categoryStats = Category::select('categories.id', 'categories.name', 'categories.code')
            ->leftJoin('equipments', 'equipments.category_id', '=', 'categories.id')
            ->leftJoin('loan_details', 'loan_details.equipment_id', '=', 'equipments.id')
            ->groupBy('categories.id', 'categories.name', 'categories.code')
            ->selectRaw('COALESCE(SUM(loan_details.qty), 0) as borrowed_qty')
            ->orderByDesc('borrowed_qty')
            ->get();

        $overdueLoans = Loan::with(['user', 'loanDetails.equipment'])
            ->overdue()
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $maintenanceEquipments = Equipment::with('category')
            ->where('qty_damaged', '>', 0)
            ->orderByDesc('qty_damaged')
            ->limit(5)
            ->get();

        $recentLogs = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'monthlyTrend',
            'categoryStats',
            'overdueLoans',
            'maintenanceEquipments',
            'recentLogs'
        ));
    }
}
