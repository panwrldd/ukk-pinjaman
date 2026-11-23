<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\RejectLoanRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Models\Equipment;
use App\Models\Loan;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoanManagementController extends Controller
{
    public function index(Request $request): View
    {
        LoanService::syncOverdueStatuses();

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $loans = Loan::with(['user', 'approvedBy', 'loanDetails.equipment'])
            ->search($search)
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->when(filled($dateFrom), fn (Builder $q) => $q->whereDate('loan_date', '>=', $dateFrom))
            ->when(filled($dateTo), fn (Builder $q) => $q->whereDate('loan_date', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $borrowers = User::where('role', 'peminjam')->where('is_active', true)->orderBy('name')->get();
        $availableEquipments = Equipment::with('category')->available()->orderBy('name')->get();

        $counts = [
            'all' => Loan::count(),
            'pending' => Loan::where('status', 'pending')->count(),
            'approved' => Loan::where('status', 'approved')->count(),
            'borrowed' => Loan::where('status', 'borrowed')->count(),
            'overdue' => Loan::overdue()->count(),
            'returned' => Loan::where('status', 'returned')->count(),
        ];

        return view('admin.loans.index', compact(
            'loans',
            'borrowers',
            'availableEquipments',
            'counts',
            'search',
            'status',
            'dateFrom',
            'dateTo'
        ));
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $borrowerId = (int) $request->input('user_id', Auth::id());
        $borrower = User::where('is_active', true)->findOrFail($borrowerId);

        try {
            $loan = LoanService::createLoan($request->validated(), $borrower->id);

            return redirect()
                ->route('admin.loans.show', $loan)
                ->with('success', "Transaksi peminjaman {$loan->code} atas nama {$borrower->name} berhasil dibuat.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
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

        return view('admin.loans.show', compact('loan'));
    }

    public function approve(Request $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::approveLoan($loan->id, (int) Auth::id(), $request->input('notes'));

            return back()->with('success', "Peminjaman {$loan->code} berhasil disetujui dan stok tersedia telah diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyetujui peminjaman: ' . $e->getMessage());
        }
    }

    public function reject(RejectLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::rejectLoan($loan->id, (int) Auth::id(), $request->validated('rejection_reason'));

            return back()->with('success', "Pengajuan peminjaman {$loan->code} telah ditolak.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menolak pengajuan: ' . $e->getMessage());
        }
    }

    public function handOver(Request $request, Loan $loan): RedirectResponse
    {
        try {
            LoanService::handOverLoan($loan->id, (int) Auth::id(), $request->input('notes'));

            return back()->with('success', "Penyerahan alat untuk peminjaman {$loan->code} berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mencatat penyerahan: ' . $e->getMessage());
        }
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        try {
            LoanService::cancelLoan($loan->id, (int) Auth::id(), true);

            return back()->with('success', "Peminjaman {$loan->code} berhasil dibatalkan.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        if (!in_array($loan->status, ['rejected', 'cancelled'], true)) {
            return back()->with(
                'error',
                'Hanya data peminjaman yang sudah Ditolak atau Dibatalkan yang boleh dihapus agar histori stok tetap akurat.'
            );
        }

        $code = $loan->code;
        $id = $loan->id;
        $loan->delete();

        ActivityLogService::log(
            action: 'loan_deleted',
            description: "Menghapus arsip pengajuan peminjaman {$code}.",
            module: 'loans',
            referenceType: 'loans',
            referenceId: $id
        );

        return redirect()
            ->route('admin.loans.index')
            ->with('success', "Data pengajuan {$code} berhasil dihapus.");
    }
}
