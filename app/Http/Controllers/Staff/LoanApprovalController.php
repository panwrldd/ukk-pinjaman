<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\RejectLoanRequest;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoanApprovalController extends Controller
{
    public function index(Request $request): View
    {
        LoanService::syncOverdueStatuses();

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $loans = Loan::with(['user', 'approvedBy', 'handedOverBy', 'loanDetails.equipment'])
            ->search($search)
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'overdue', 'borrowed', 'returned', 'rejected', 'cancelled')")
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $tabCounts = [
            'all' => Loan::count(),
            'pending' => Loan::where('status', 'pending')->count(),
            'approved' => Loan::where('status', 'approved')->count(),
            'borrowed' => Loan::whereIn('status', ['borrowed', 'overdue'])->count(),
            'overdue' => Loan::overdue()->count(),
        ];

        return view('staff.loans.index', compact('loans', 'tabCounts', 'search', 'status'));
    }

    public function show(Loan $loan): View
    {
        LoanService::syncOverdueStatuses();
        $loan->refresh()->load([
            'user',
            'approvedBy',
            'handedOverBy',
            'loanDetails.equipment.category',
            'equipmentReturn.returnDetails.equipment',
            'equipmentReturn.verifiedBy',
        ]);

        return view('staff.loans.show', compact('loan'));
    }

    public function approve(Request $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::approveLoan($loan->id, (int) Auth::id(), $request->input('notes'));

            return back()->with(
                'success',
                "Pengajuan {$loan->code} berhasil disetujui. Stok peralatan telah direservasi dan siap diserahkan."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Persetujuan gagal: ' . $e->getMessage());
        }
    }

    public function reject(RejectLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::rejectLoan($loan->id, (int) Auth::id(), $request->validated('rejection_reason'));

            return back()->with('success', "Pengajuan {$loan->code} telah ditolak beserta alasan penolakan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Penolakan gagal: ' . $e->getMessage());
        }
    }

    public function handOver(Request $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::handOverLoan($loan->id, (int) Auth::id(), $request->input('notes'));

            return back()->with('success', "Penyerahan fisik peralatan untuk {$loan->code} berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mencatat penyerahan: ' . $e->getMessage());
        }
    }

    public function cancelApproved(Loan $loan): RedirectResponse
    {
        try {
            LoanService::cancelLoan($loan->id, (int) Auth::id(), true);

            return back()->with('success', "Peminjaman {$loan->code} dibatalkan dan stok reservasi telah dikembalikan.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
