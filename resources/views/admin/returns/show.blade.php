@extends('layouts.app')

@section('title', 'Verifikasi Pengembalian ' . $equipmentReturn->code)
@section('header', 'Pemeriksaan & Verifikasi Pengembalian')
@section('subheader', 'Periksa kondisi fisik setiap alat, hitung denda otomatis via fn_calculate_fine, dan perbarui stok melalui sp_process_return.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.returns.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Daftar Pengembalian</span>
        </a>
        <x-status-badge :variant="$equipmentReturn->status_badge" :label="$equipmentReturn->status_label" />
    </div>

    @include('partials.return-verification-panel', [
        'equipmentReturn' => $equipmentReturn,
        'finePreview' => $finePreview,
        'verifyRoute' => route('admin.returns.verify', $equipmentReturn),
        'markPaidRoute' => route('admin.returns.mark-paid', $equipmentReturn),
        'loanShowRoute' => route('admin.loans.show', $equipmentReturn->loan_id),
    ])
@endsection
