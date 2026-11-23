<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak — {{ $report['meta']['title'] }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #fff; color: #0f172a; font-family: 'Inter', 'Segoe UI', Arial, sans-serif; }
        @page { size: A4 landscape; margin: 12mm; }
        table.print-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 12px; }
        table.print-table th, table.print-table td { border: 1px solid #334155; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.print-table th { background-color: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 10px; }
    </style>
</head>
<body class="p-6">
    <!-- Top Print Action Bar (Hidden when printing) -->
    <div class="no-print mb-6 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
        <span class="text-xs font-medium text-slate-600">Pratinjau Cetak Resmi — Tekan <strong>Cetak Sekarang</strong> untuk mencetak ke printer atau simpan PDF.</span>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="btn-primary btn-xs">Cetak Sekarang</button>
            <button onclick="window.close()" class="btn-secondary btn-xs">Tutup</button>
        </div>
    </div>

    <!-- Official Institution Letterhead -->
    <div class="border-b-2 border-slate-900 pb-3 text-center">
        <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900">{{ $institution['name'] }}</h1>
        <p class="text-xs font-semibold uppercase text-slate-700">{{ $institution['unit'] }}</p>
        <p class="mt-0.5 text-[11px] text-slate-600">{{ $institution['address'] }} · Telp: {{ $institution['phone'] }}</p>
    </div>

    <div class="mt-4 flex items-start justify-between text-xs">
        <div>
            <h2 class="text-base font-bold uppercase text-slate-900">{{ $report['meta']['title'] }}</h2>
            <p class="text-slate-600">
                Periode:
                @if(!empty($filters['date_from']) || !empty($filters['date_to']))
                    {{ $filters['date_from'] ?: 'Awal' }} s/d {{ $filters['date_to'] ?: 'Sekarang' }}
                @else
                    Semua Periode Data Aktual
                @endif
            </p>
        </div>
        <div class="text-right text-[11px] text-slate-600">
            <p>Tanggal Cetak: <strong>{{ now()->translatedFormat('d F Y, H:i') }} WIB</strong></p>
            <p>Dicetak Oleh: <strong>{{ auth()->user()->name }} ({{ auth()->user()->role_label }})</strong></p>
        </div>
    </div>

    <!-- Summary Row -->
    <div class="mt-3 grid grid-cols-4 gap-3">
        @foreach($report['summary'] as $stat)
            <div class="rounded border border-slate-300 px-3 py-2">
                <span class="block text-[10px] font-semibold uppercase text-slate-500">{{ $stat['label'] }}</span>
                <span class="text-sm font-bold text-slate-900">{{ $stat['value'] }}</span>
            </div>
        @endforeach
    </div>

    <!-- Data Table -->
    <table class="print-table">
        <thead>
            <tr>
                @foreach($report['headings'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['headings']) }}" style="text-align:center; padding: 16px;">Tidak ada data laporan pada kriteria ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Footer -->
    <div class="mt-8 flex justify-end text-xs">
        <div class="w-64 text-center">
            <p>{{ now()->translatedFormat('d F Y') }}</p>
            <p class="font-semibold">{{ auth()->user()->role_label }}</p>
            <div class="h-16"></div>
            <p class="font-bold underline">{{ auth()->user()->name }}</p>
            <p class="text-[11px] text-slate-600">{{ auth()->user()->identity_number ? 'NIP/ID: ' . auth()->user()->identity_number : '@' . auth()->user()->username }}</p>
        </div>
    </div>
</body>
</html>
