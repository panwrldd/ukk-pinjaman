<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoanRequestController extends Controller
{
    public function index(Request $request): View
    {
        LoanService::syncOverdueStatuses();

        $userId = (int) Auth::id();
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $loans = Loan::with(['loanDetails.equipment', 'equipmentReturn', 'approvedBy'])
            ->where('user_id', $userId)
            ->search($search)
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'all' => Loan::where('user_id', $userId)->count(),
            'pending' => Loan::where('user_id', $userId)->where('status', 'pending')->count(),
            'approved' => Loan::where('user_id', $userId)->where('status', 'approved')->count(),
            'borrowed' => Loan::where('user_id', $userId)->whereIn('status', ['borrowed', 'overdue'])->count(),
            'returned' => Loan::where('user_id', $userId)->where('status', 'returned')->count(),
        ];

        return view('borrower.loans.index', compact('loans', 'counts', 'status', 'search'));
    }

    public function create(Request $request): View
    {
        $selectedEquipmentId = $request->integer('equipment_id') ?: null;

        $availableEquipments = Equipment::with('category')
            ->available()
            ->orderBy('name')
            ->get();

        $maxLoanDays = (int) Setting::get('max_loan_days', 7);
        $fineRate = Setting::fineRatePerDay();

        return view('borrower.loans.create', compact(
            'availableEquipments',
            'selectedEquipmentId',
            'maxLoanDays',
            'fineRate'
        ));
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        try {
            $loan = LoanService::createLoan($request->validated(), (int) Auth::id());

            return redirect()
                ->route('borrower.loans.show', $loan)
                ->with('success', "Pengajuan peminjaman {$loan->code} berhasil dikirim dan menunggu verifikasi petugas.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Loan $loan): View
    {
        Gate::authorize('view', $loan);

        LoanService::syncOverdueStatuses();
        $loan->refresh()->load([
            'loanDetails.equipment.category',
            'approvedBy',
            'handedOverBy',
            'equipmentReturn.returnDetails.equipment',
            'equipmentReturn.verifiedBy',
        ]);

        $fineRate = Setting::fineRatePerDay();

        return view('borrower.loans.show', compact('loan', 'fineRate'));
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        Gate::authorize('cancel', $loan);

        try {
            LoanService::cancelLoan($loan->id, (int) Auth::id(), false);

            return back()->with('success', "Pengajuan peminjaman {$loan->code} berhasil dibatalkan.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
