<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->is_active && ($user->isPeminjam() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'loan_date' => ['required', 'date', 'after_or_equal:today'],
            'due_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'loan_date.required' => 'Tanggal peminjaman wajib diisi.',
            'loan_date.after_or_equal' => 'Tanggal peminjaman minimal hari ini.',
            'due_date.required' => 'Tanggal rencana pengembalian wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.',
            'purpose.required' => 'Keperluan peminjaman wajib dijelaskan.',
            'purpose.min' => 'Keperluan peminjaman minimal 5 karakter.',
            'items.required' => 'Pilih minimal satu peralatan yang ingin dipinjam.',
            'items.min' => 'Pilih minimal satu peralatan yang ingin dipinjam.',
            'items.*.equipment_id.required' => 'Peralatan wajib dipilih.',
            'items.*.equipment_id.exists' => 'Peralatan yang dipilih tidak valid.',
            'items.*.qty.required' => 'Jumlah pinjam wajib diisi.',
            'items.*.qty.min' => 'Jumlah pinjam minimal 1 unit.',
        ];
    }
}
