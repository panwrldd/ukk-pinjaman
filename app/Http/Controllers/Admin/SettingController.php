<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::pluck('value', 'key');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $adminId = (int) Auth::id();

        DB::beginTransaction();
        try {
            $oldFineRate = Setting::get('fine_rate_per_day', 5000);

            Setting::set('fine_rate_per_day', $validated['fine_rate_per_day'], $adminId, 'integer');
            Setting::set('max_loan_days', $validated['max_loan_days'], $adminId, 'integer');
            Setting::set('max_active_loans', $validated['max_active_loans'], $adminId, 'integer');
            Setting::set('institution_name', $validated['institution_name'], $adminId, 'string');
            Setting::set('institution_unit', $validated['institution_unit'] ?? '', $adminId, 'string');
            Setting::set('institution_address', $validated['institution_address'] ?? '', $adminId, 'string');
            Setting::set('institution_phone', $validated['institution_phone'] ?? '', $adminId, 'string');

            ActivityLogService::log(
                action: 'settings_updated',
                description: sprintf(
                    'Memperbarui pengaturan sistem (Tarif denda: Rp%s -> Rp%s / hari).',
                    number_format((int) $oldFineRate, 0, ',', '.'),
                    number_format((int) $validated['fine_rate_per_day'], 0, ',', '.')
                ),
                module: 'settings',
                userId: $adminId
            );

            DB::commit();

            return back()->with('success', 'Pengaturan aplikasi dan tarif denda berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
        }
    }
}
