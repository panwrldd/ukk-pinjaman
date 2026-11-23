@props([
    'icon' => 'box',
    'title' => 'Belum ada data',
    'description' => 'Data yang Anda cari belum tersedia saat ini.',
])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $description }}</p>
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
