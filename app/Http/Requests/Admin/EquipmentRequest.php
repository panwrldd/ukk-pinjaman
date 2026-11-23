<?php

namespace App\Http\Requests\Admin;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Equipment|int|string|null $routeEquipment */
        $routeEquipment = $this->route('equipment');
        $equipmentId = $routeEquipment instanceof Equipment ? $routeEquipment->id : $routeEquipment;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('equipments', 'code')->ignore($equipmentId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'condition' => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'qty_total' => ['required', 'integer', 'min:1', 'max:10000'],
            'location' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['tersedia', 'tidak_tersedia', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (!$isUpdate) {
            $rules['qty_available'] = ['nullable', 'integer', 'min:0', 'lte:qty_total'];
            $rules['qty_damaged'] = ['nullable', 'integer', 'min:0', 'lte:qty_total'];
        } else {
            $rules['qty_damaged'] = ['nullable', 'integer', 'min:0', 'lte:qty_total'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $qtyTotal = (int) $this->input('qty_total', 0);
                $qtyDamaged = (int) $this->input('qty_damaged', 0);

                /** @var Equipment|null $routeEquipment */
                $routeEquipment = $this->route('equipment');

                if ($routeEquipment instanceof Equipment) {
                    $currentlyBorrowed = $routeEquipment->qty_borrowed;
                    if ($qtyTotal < ($currentlyBorrowed + $qtyDamaged)) {
                        $validator->errors()->add(
                            'qty_total',
                            "Total stok tidak boleh kurang dari jumlah unit yang sedang dipinjam ({$currentlyBorrowed}) ditambah unit rusak ({$qtyDamaged})."
                        );
                    }
                } else {
                    $qtyAvailable = $this->filled('qty_available')
                        ? (int) $this->input('qty_available')
                        : ($qtyTotal - $qtyDamaged);

                    if (($qtyAvailable + $qtyDamaged) > $qtyTotal) {
                        $validator->errors()->add(
                            'qty_available',
                            'Jumlah stok tersedia ditambah stok rusak tidak boleh melebihi total stok.'
                        );
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori alat wajib dipilih.',
            'category_id.exists' => 'Kategori alat tidak ditemukan.',
            'code.required' => 'Kode inventaris alat wajib diisi.',
            'code.regex' => 'Kode alat hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
            'code.unique' => 'Kode inventaris alat tersebut sudah digunakan.',
            'name.required' => 'Nama peralatan wajib diisi.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'qty_total.required' => 'Total stok wajib diisi.',
            'qty_total.min' => 'Total stok minimal 1 unit.',
            'qty_available.min' => 'Stok tersedia tidak boleh bernilai negatif.',
            'qty_available.lte' => 'Stok tersedia tidak boleh melebihi total stok.',
        ];
    }
}
