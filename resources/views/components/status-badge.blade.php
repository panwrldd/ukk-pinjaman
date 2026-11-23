@props([
    'variant' => 'slate',
    'label' => '',
])

@php
    $classes = match ($variant) {
        'emerald', 'green', 'tersedia', 'baik', 'returned', 'verified', 'active' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
        'amber', 'yellow', 'pending', 'pending_verification', 'rusak_ringan' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'blue', 'approved', 'petugas' => 'bg-blue-50 text-blue-800 ring-blue-600/20',
        'indigo', 'borrowed', 'admin' => 'bg-indigo-50 text-indigo-800 ring-indigo-600/20',
        'red', 'rose', 'overdue', 'rejected', 'rusak_berat' => 'bg-rose-50 text-rose-800 ring-rose-600/20',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    {{ $label ?: $slot }}
</span>
