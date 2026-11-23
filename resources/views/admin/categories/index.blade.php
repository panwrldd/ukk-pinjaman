@extends('layouts.app')

@section('title', 'Manajemen Kategori Alat')
@section('header', 'Manajemen Kategori Peralatan')
@section('subheader', 'Kelola klasifikasi inventaris peralatan multimedia dan laboratorium.')

@section('content')
    <div x-data="{
        modalOpen: @js($errors->any()),
        isEdit: false,
        formAction: @js(route('admin.categories.store')),
        form: {
            code: @js(old('code', '')),
            name: @js(old('name', '')),
            description: @js(old('description', '')),
            is_active: true
        },
        openCreate() {
            this.isEdit = false;
            this.formAction = @js(route('admin.categories.store'));
            this.form = { code: '', name: '', description: '', is_active: true };
            this.modalOpen = true;
        },
        openEdit(cat, updateUrl) {
            this.isEdit = true;
            this.formAction = updateUrl;
            this.form = {
                code: cat.code,
                name: cat.name,
                description: cat.description || '',
                is_active: Boolean(cat.is_active)
            };
            this.modalOpen = true;
        }
    }">
        <!-- Filter & Create Bar -->
        <div class="panel mb-5 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-1 flex-wrap items-center gap-2.5">
                    <div class="relative min-w-[240px] flex-1 max-w-md">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode atau nama kategori..." class="input-field pl-9">
                    </div>
                    <select name="status" class="input-field w-auto">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>
                    <button type="submit" class="btn-secondary">Filter</button>
                    @if($search !== '' || $status !== '')
                        <a href="{{ route('admin.categories.index') }}" class="btn-secondary">Reset</a>
                    @endif
                </form>

                <button type="button" @click="openCreate()" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>Tambah Kategori</span>
                </button>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="panel">
            @if($categories->isEmpty())
                <x-empty-state icon="tags" title="Belum Ada Kategori" description="Tambahkan kategori untuk mengelompokkan peralatan inventaris laboratorium." />
            @else
                <div class="table-container">
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Alat</th>
                                <th>Total / Tersedia</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-slate-900">{{ $cat->code }}</td>
                                    <td class="font-semibold text-slate-900">{{ $cat->name }}</td>
                                    <td class="max-w-xs truncate text-xs text-slate-600">{{ $cat->description ?: '-' }}</td>
                                    <td class="text-xs font-medium text-slate-700">{{ $cat->equipments_count }} jenis</td>
                                    <td class="text-xs text-slate-700">
                                        <span class="font-semibold text-slate-900">{{ (int) $cat->available_units }}</span> / {{ (int) $cat->total_units }} unit
                                    </td>
                                    <td>
                                        <x-status-badge :variant="$cat->is_active ? 'active' : 'slate'" :label="$cat->is_active ? 'Aktif' : 'Nonaktif'" />
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEdit(@js($cat->only(['code', 'name', 'description', 'is_active'])), @js(route('admin.categories.update', $cat)))"
                                                class="btn-secondary btn-xs"
                                            >
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span>Edit</span>
                                            </button>

                                            @if($cat->equipments_count === 0)
                                                <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori {{ addslashes($cat->name) }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger-outline btn-xs">
                                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($categories->hasPages())
                    <div class="border-t border-slate-200 px-5 py-3.5">
                        {{ $categories->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Category Modal -->
        <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="modalOpen = false">
            <div @click.away="modalOpen = false" class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Kategori Alat' : 'Tambah Kategori Baru'"></h3>
                    <button type="button" @click="modalOpen = false" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <form method="POST" :action="formAction" class="space-y-4 p-5">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="input-label">Kode Kategori <span class="text-rose-600">*</span></label>
                        <input type="text" name="code" x-model="form.code" required maxlength="20" placeholder="Contoh: KAT-KAM" class="input-field font-mono uppercase">
                    </div>

                    <div>
                        <label class="input-label">Nama Kategori <span class="text-rose-600">*</span></label>
                        <input type="text" name="name" x-model="form.name" required maxlength="100" placeholder="Contoh: Kamera & Videografi" class="input-field">
                    </div>

                    <div>
                        <label class="input-label">Deskripsi</label>
                        <textarea name="description" x-model="form.description" rows="3" placeholder="Penjelasan singkat cakupan peralatan..." class="input-field"></textarea>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span class="text-xs font-semibold text-slate-800">Kategori Aktif (Tampil pada Katalog Peminjam)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" @click="modalOpen = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
