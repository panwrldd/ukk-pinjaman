<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function store(Request $request, Loan $loan): RedirectResponse
    {
        if ($loan->user_id !== (int) Auth::id()) {
            abort(403, 'Anda tidak berhak mengajukan pengembalian untuk transaksi milik pengguna lain.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $equipmentReturn = ReturnService::submitReturn(
                $loan->id,
                (int) Auth::id(),
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('borrower.loans.show', $loan)
                ->with(
                    'success',
                    "Pengajuan pengembalian ({$equipmentReturn->code}) berhasil dibuat. Silakan serahkan fisik peralatan kepada Petugas Laboratorium untuk diverifikasi."
                );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
