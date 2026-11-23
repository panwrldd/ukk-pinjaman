<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $categories = Category::withCount('equipments')
            ->withSum('equipments as total_units', 'qty_total')
            ->withSum('equipments as available_units', 'qty_available')
            ->search($search)
            ->when($status === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'search', 'status'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::beginTransaction();
        try {
            $category = Category::create($validated);

            ActivityLogService::log(
                action: 'category_created',
                description: "Menambahkan kategori baru: {$category->name} ({$category->code}).",
                module: 'categories',
                referenceType: 'categories',
                referenceId: $category->id,
                newValues: $category->only(['code', 'name', 'is_active'])
            );

            DB::commit();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "Kategori {$category->name} berhasil ditambahkan.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menambahkan kategori: ' . $e->getMessage());
        }
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', false);

        DB::beginTransaction();
        try {
            $oldValues = $category->only(['code', 'name', 'is_active']);
            $category->update($validated);

            ActivityLogService::log(
                action: 'category_updated',
                description: "Memperbarui kategori: {$category->name} ({$category->code}).",
                module: 'categories',
                referenceType: 'categories',
                referenceId: $category->id,
                oldValues: $oldValues,
                newValues: $category->only(['code', 'name', 'is_active'])
            );

            DB::commit();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "Kategori {$category->name} berhasil diperbarui.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->equipments()->exists()) {
            return back()->with(
                'error',
                "Kategori {$category->name} tidak dapat dihapus karena masih memiliki data peralatan inventaris."
            );
        }

        DB::beginTransaction();
        try {
            $name = $category->name;
            $code = $category->code;
            $id = $category->id;

            $category->delete();

            ActivityLogService::log(
                action: 'category_deleted',
                description: "Menghapus kategori: {$name} ({$code}).",
                module: 'categories',
                referenceType: 'categories',
                referenceId: $id
            );

            DB::commit();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "Kategori {$name} berhasil dihapus.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }
}
