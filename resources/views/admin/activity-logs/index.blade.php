@extends('layouts.app')

@section('title', 'Log Aktivitas & Audit')
@section('header', 'Log Aktivitas & Audit Database')
@section('subheader', 'Rekaman aktivitas seluruh pengguna beserta trigger audit otomatis dari mesin database MariaDB.')

@section('content')
    <div class="panel mb-5 p-4">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-12">
            <div class="relative sm:col-span-4">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari aksi, deskripsi, atau nama pengguna..." class="input-field pl-9">
            </div>
            <div class="sm:col-span-2">
                <select name="module" class="input-field">
                    <option value="">Semua Modul</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" @selected($module === $mod)>{{ strtoupper($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field" title="Dari Tanggal">
            </div>
            <div class="sm:col-span-2">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field" title="Sampai Tanggal">
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <button type="submit" class="btn-primary flex-1">Filter</button>
                @if($search !== '' || $module !== '' || $dateFrom !== '' || $dateTo !== '')
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="panel">
        @if($logs->isEmpty())
            <x-empty-state icon="activity" title="Belum Ada Catatan Aktivitas" description="Tidak ada log aktivitas yang sesuai dengan filter saat ini." />
        @else
            <div class="table-container">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pelaku</th>
                            <th>Modul</th>
                            <th>Aksi</th>
                            <th>Deskripsi & Perubahan Nilai</th>
                            <th>Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-slate-500">
                                    <span class="block font-medium text-slate-800">{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
                                    <span>{{ $log->created_at?->diffForHumans() }}</span>
                                </td>
                                <td>
                                    @if($log->user)
                                        <p class="text-xs font-semibold text-slate-900">{{ $log->user->name }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $log->user->role_label }}</p>
                                    @else
                                        <span class="text-xs font-medium text-slate-500">Sistem / Trigger DB</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$log->module === 'db_trigger' ? 'indigo' : 'slate'" :label="strtoupper($log->module)" />
                                </td>
                                <td class="font-mono text-xs font-semibold text-slate-800">{{ $log->action }}</td>
                                <td class="max-w-md text-xs text-slate-700">
                                    <p>{{ $log->description }}</p>
                                    @if($log->old_values || $log->new_values)
                                        <div class="mt-1 rounded border border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[11px] text-slate-600">
                                            @if($log->old_values)
                                                <span>Lama: {{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE) }}</span>
                                            @endif
                                            @if($log->new_values)
                                                <span class="ml-2 text-blue-800">Baru: {{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="font-mono text-xs text-slate-500">{{ $log->ip_address ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-slate-200 px-5 py-3.5">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
