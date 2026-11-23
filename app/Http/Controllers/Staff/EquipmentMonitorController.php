<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentMonitorController extends Controller
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
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('staff.equipments.index', compact(
            'equipments',
            'categories',
            'search',
            'categoryId',
            'condition',
            'status'
        ));
    }
}
