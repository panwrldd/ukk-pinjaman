@extends('layouts.app')

@section('title', 'Verifikasi Pengembalian ' . $equipmentReturn->code)
@section('header', 'Verifikasi Kondisi Alat & Denda')
@section('subheader', 'Periksa fisik peralatan yang dikembalikan dan konfirmasi perhitungan denda keterlambatan.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('staff.returns.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            <span>Kembali ke Daftar Pengembalian</span>
        </a>
        <x-status-badge :variant="$equipmentReturn->status_badge" :label="$equipmentReturn->status_label" />
    </div>

    @include('partials.return-verification-panel', [
        'equipmentReturn' => $equipmentReturn,
        'finePreview' => $finePreview,
        'verifyRoute' => route('staff.returns.verify', $equipmentReturn),
        'markPaidRoute' => route('staff.returns.mark-paid', $equipmentReturn),
        'loanShowRoute' => route('staff.loans.show', $equipmentReturn->loan_id),
    ])
@endsection
