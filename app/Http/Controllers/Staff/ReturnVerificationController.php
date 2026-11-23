<?php

namespace App\Http\Controllers\Staff;

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

class ReturnVerificationController extends Controller
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

        $counts = [
            'pending' => EquipmentReturn::where('status', 'pending_verification')->count(),
            'verified' => EquipmentReturn::where('status', 'verified')->count(),
            'active_unreturned' => $activeBorrowedLoans->count(),
        ];

        return view('staff.returns.index', compact(
            'returns',
            'activeBorrowedLoans',
            'counts',
            'search',
            'status'
        ));
    }

    public function initiateFromDesk(Request $request, Loan $loan): RedirectResponse
    {
        try {
            $equipmentReturn = ReturnService::submitReturn(
                $loan->id,
                $loan->user_id,
                $request->input('notes', 'Diserahkan langsung di loket petugas.')
            );

            return redirect()
                ->route('staff.returns.show', $equipmentReturn)
                ->with('success', "Pengembalian {$equipmentReturn->code} siap diperiksa kondisi fisiknya.");
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

        return view('staff.returns.show', compact('equipmentReturn', 'finePreview'));
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
                ->route('staff.returns.show', $verified)
                ->with('success', "Pengembalian {$verified->code} selesai diverifikasi dan stok diperbarui.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Verifikasi gagal: ' . $e->getMessage());
        }
    }

    public function markFinePaid(EquipmentReturn $equipmentReturn): RedirectResponse
    {
        if ($equipmentReturn->fine_amount <= 0) {
            return back()->with('info', 'Tidak ada denda pada pengembalian ini.');
        }

        $equipmentReturn->update(['fine_paid' => true]);

        ActivityLogService::log(
            action: 'fine_marked_paid',
            description: "Petugas mencatat pelunasan denda {$equipmentReturn->formatted_fine} pada {$equipmentReturn->code}.",
            module: 'returns',
            referenceType: 'returns',
            referenceId: $equipmentReturn->id
        );

        return back()->with('success', "Pembayaran denda {$equipmentReturn->formatted_fine} telah dikonfirmasi lunas.");
    }
}
