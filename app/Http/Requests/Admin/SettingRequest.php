<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'fine_rate_per_day' => ['required', 'integer', 'min:0', 'max:1000000'],
            'max_loan_days' => ['required', 'integer', 'min:1', 'max:60'],
            'max_active_loans' => ['required', 'integer', 'min:1', 'max:20'],
            'institution_name' => ['required', 'string', 'max:150'],
            'institution_unit' => ['nullable', 'string', 'max:150'],
            'institution_address' => ['nullable', 'string', 'max:300'],
            'institution_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'fine_rate_per_day.required' => 'Tarif denda per hari wajib diisi.',
            'fine_rate_per_day.integer' => 'Tarif denda harus berupa angka bulat (Rupiah).',
            'fine_rate_per_day.min' => 'Tarif denda tidak boleh bernilai negatif.',
            'max_loan_days.required' => 'Durasi maksimal peminjaman wajib diisi.',
            'max_active_loans.required' => 'Batas pinjaman aktif per peminjam wajib diisi.',
            'institution_name.required' => 'Nama institusi wajib diisi.',
        ];
    }
}
