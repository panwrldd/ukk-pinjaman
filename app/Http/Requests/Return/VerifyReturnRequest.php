<?php

namespace App\Http\Requests\Return;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isPetugas() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'details' => ['required', 'array', 'min:1'],
            'details.*.loan_detail_id' => ['required', 'integer', 'exists:loan_details,id'],
            'details.*.condition_after' => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'details.*.qty_damaged' => ['nullable', 'integer', 'min:0'],
            'details.*.damage_notes' => ['nullable', 'string', 'max:500'],
            'fine_paid' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'details.required' => 'Data pemeriksaan kondisi alat wajib diisi.',
            'details.*.condition_after.required' => 'Kondisi pengembalian setiap alat wajib dipilih.',
            'details.*.condition_after.in' => 'Pilihan kondisi alat tidak valid.',
        ];
    }
}
