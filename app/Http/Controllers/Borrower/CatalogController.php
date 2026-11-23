<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $categoryId = $request->string('category_id')->toString();
        $onlyAvailable = $request->boolean('available_only');

        $equipments = Equipment::with('category')
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->search($search)
            ->when(filled($categoryId), fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when($onlyAvailable, fn (Builder $q) => $q->where('status', 'tersedia')->where('qty_available', '>', 0))
            ->orderByRaw("FIELD(status, 'tersedia', 'tidak_tersedia', 'maintenance')")
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        $categories = Category::active()->withCount('equipments')->orderBy('name')->get();

        return view('borrower.catalog.index', compact(
            'equipments',
            'categories',
            'search',
            'categoryId',
            'onlyAvailable'
        ));
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load('category');

        if (!$equipment->category || !$equipment->category->is_active) {
            abort(404, 'Peralatan tidak ditemukan pada katalog aktif.');
        }

        $relatedEquipments = Equipment::with('category')
            ->available()
            ->where('category_id', $equipment->category_id)
            ->where('id', '!=', $equipment->id)
            ->limit(4)
            ->get();

        return view('borrower.catalog.show', compact('equipment', 'relatedEquipments'));
    }
}
