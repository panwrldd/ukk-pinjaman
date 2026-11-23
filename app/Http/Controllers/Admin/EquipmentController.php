<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EquipmentRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $categoryId = $request->string('category_id')->toString();
        $condition = $request->string('condition')->toString();
        $status = $request->string('status')->toString();

        $equipments = Equipment::with('category')
            ->search($search)
            ->when(filled($categoryId), fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when(filled($condition), fn (Builder $q) => $q->where('condition', $condition))
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        $summary = [
            'total_items' => Equipment::count(),
            'total_units' => (int) Equipment::sum('qty_total'),
            'available_units' => (int) Equipment::sum('qty_available'),
            'damaged_units' => (int) Equipment::sum('qty_damaged'),
        ];

        return view('admin.equipments.index', compact(
            'equipments',
            'categories',
            'summary',
            'search',
            'categoryId',
            'condition',
            'status'
        ));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $equipment = new Equipment([
            'condition' => 'baik',
            'status' => 'tersedia',
            'qty_total' => 1,
            'qty_available' => 1,
            'qty_damaged' => 0,
        ]);

        return view('admin.equipments.form', compact('equipment', 'categories'));
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        try {
            $equipment = EquipmentService::createEquipment(
                $request->validated(),
                $request->file('photo'),
                (int) Auth::id()
            );

            return redirect()
                ->route('admin.equipments.index')
                ->with('success', "Peralatan {$equipment->name} ({$equipment->code}) berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load([
            'category',
            'loanDetails' => fn ($q) => $q->with(['loan.user', 'returnDetail'])->latest()->limit(15),
        ]);

        return view('admin.equipments.show', compact('equipment'));
    }

    public function edit(Equipment $equipment): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.equipments.form', compact('equipment', 'categories'));
    }

    public function update(EquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        try {
            $updated = EquipmentService::updateEquipment(
                $equipment,
                $request->validated(),
                $request->file('photo'),
                $request->boolean('remove_photo'),
                (int) Auth::id()
            );

            return redirect()
                ->route('admin.equipments.index')
                ->with('success', "Data peralatan {$updated->name} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function restoreMaintenance(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'qty_restore' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'qty_restore.required' => 'Jumlah unit yang dipulihkan wajib diisi.',
            'qty_restore.min' => 'Minimal 1 unit yang dipulihkan.',
        ]);

        try {
            $restored = EquipmentService::restoreDamagedUnits(
                $equipment,
                (int) $validated['qty_restore'],
                (int) Auth::id(),
                $validated['notes'] ?? null
            );

            return back()->with(
                'success',
                "{$validated['qty_restore']} unit {$restored->name} telah selesai diperbaiki dan dikembalikan ke stok tersedia."
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        try {
            $name = $equipment->name;
            EquipmentService::deleteEquipment($equipment, (int) Auth::id());

            return redirect()
                ->route('admin.equipments.index')
                ->with('success', "Peralatan {$name} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
