<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['meta']['title'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 10px;
            color: #334155;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .meta-table td {
            vertical-align: top;
            font-size: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary-table td {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 6px 8px;
            width: 25%;
        }
        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .summary-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th, table.data td {
            border: 1px solid #475569;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.data tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .signature {
            margin-top: 24px;
            width: 100%;
        }
        .signature td {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $institution['name'] }}</h1>
        <p><strong>{{ $institution['unit'] }}</strong></p>
        <p>{{ $institution['address'] }} | Telp: {{ $institution['phone'] }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong style="font-size: 12px; text-transform: uppercase;">{{ $report['meta']['title'] }}</strong><br>
                Periode:
                @if(!empty($filters['date_from']) || !empty($filters['date_to']))
                    {{ $filters['date_from'] ?: 'Awal' }} s/d {{ $filters['date_to'] ?: 'Sekarang' }}
                @else
                    Semua Data Aktual
                @endif
            </td>
            <td style="text-align: right;">
                Dicetak: {{ now()->format('d/m/Y H:i') }} WIB<br>
                Petugas: {{ auth()->user()->name }} ({{ auth()->user()->role_label }})
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            @foreach($report['summary'] as $stat)
                <td>
                    <div class="summary-label">{{ $stat['label'] }}</div>
                    <div class="summary-value">{{ $stat['value'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
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
                    <td colspan="{{ count($report['headings']) }}" style="text-align: center; padding: 14px;">Tidak ada data pada laporan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%;">
                {{ now()->translatedFormat('d F Y') }}<br>
                <strong>{{ auth()->user()->role_label }}</strong>
                <br><br><br><br>
                <strong><u>{{ auth()->user()->name }}</u></strong><br>
                {{ auth()->user()->identity_number ? 'NIP/ID: ' . auth()->user()->identity_number : '@' . auth()->user()->username }}
            </td>
        </tr>
    </table>
</body>
</html>
