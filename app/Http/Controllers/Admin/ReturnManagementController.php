<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Return\VerifyReturnRequest;
use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Services\ActivityLogService;
use App\Services\LoanService;
use App\Services\ReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReturnManagementController extends Controller
{
    public function index(Request $request): View
    {
        LoanService::syncOverdueStatuses();

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $returns = EquipmentReturn::with(['loan.user', 'returnedBy', 'verifiedBy', 'returnDetails.equipment'])
            ->search($search)
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending_verification', 'verified')")
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $activeBorrowedLoans = Loan::with(['user', 'loanDetails.equipment'])
            ->whereIn('status', ['borrowed', 'overdue'])
            ->doesntHave('equipmentReturn')
            ->orderBy('due_date')
            ->get();

        $summary = [
            'pending' => EquipmentReturn::where('status', 'pending_verification')->count(),
            'verified' => EquipmentReturn::where('status', 'verified')->count(),
            'unpaid_fines' => (float) EquipmentReturn::where('fine_paid', false)->sum('fine_amount'),
            'paid_fines' => (float) EquipmentReturn::where('fine_paid', true)->sum('fine_amount'),
        ];

        return view('admin.returns.index', compact(
            'returns',
            'activeBorrowedLoans',
            'summary',
            'search',
            'status'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'return_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $loan = Loan::findOrFail($validated['loan_id']);
            $equipmentReturn = ReturnService::submitReturn(
                $loan->id,
                $loan->user_id,
                $validated['notes'] ?? null,
                $validated['return_date'] ?? null
            );

            return redirect()
                ->route('admin.returns.show', $equipmentReturn)
                ->with('success', "Data pengembalian {$equipmentReturn->code} berhasil dicatat. Silakan verifikasi kondisi fisik alat.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(EquipmentReturn $equipmentReturn): View
    {
        $equipmentReturn->load([
            'loan.user',
            'loan.loanDetails.equipment.category',
            'returnedBy',
            'verifiedBy',
            'returnDetails.equipment',
        ]);

        $finePreview = $equipmentReturn->status === 'pending_verification'
            ? ReturnService::previewFine(
                $equipmentReturn->loan->due_date->toDateString(),
                $equipmentReturn->return_date->toDateString()
            )
            : [
                'late_days' => $equipmentReturn->late_days,
                'fine_amount' => (float) $equipmentReturn->fine_amount,
            ];

        return view('admin.returns.show', compact('equipmentReturn', 'finePreview'));
    }

    public function verify(VerifyReturnRequest $request, EquipmentReturn $equipmentReturn): RedirectResponse
    {
        try {
            $verified = ReturnService::verifyReturn(
                $equipmentReturn->id,
                (int) Auth::id(),
                $request->validated('details'),
                $request->boolean('fine_paid'),
                $request->validated('notes')
            );

            return redirect()
                ->route('admin.returns.show', $verified)
                ->with('success', "Pengembalian {$verified->code} berhasil diverifikasi dan stok diperbarui.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Verifikasi gagal: ' . $e->getMessage());
        }
    }

    public function markFinePaid(EquipmentReturn $equipmentReturn): RedirectResponse
    {
        if ($equipmentReturn->fine_amount <= 0) {
            return back()->with('info', 'Transaksi ini tidak memiliki tagihan denda.');
        }

        $equipmentReturn->update(['fine_paid' => true]);

        ActivityLogService::log(
            action: 'fine_marked_paid',
            description: "Menandai pelunasan denda sebesar {$equipmentReturn->formatted_fine} untuk pengembalian {$equipmentReturn->code}.",
            module: 'returns',
            referenceType: 'returns',
            referenceId: $equipmentReturn->id
        );

        return back()->with('success', "Denda sebesar {$equipmentReturn->formatted_fine} telah ditandai lunas.");
    }
}
