<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        $settings = [
            [
                'key' => 'fine_rate_per_day',
                'value' => '5000',
                'type' => 'integer',
                'description' => 'Tarif denda keterlambatan pengembalian alat per hari (Rupiah).',
            ],
            [
                'key' => 'max_loan_days',
                'value' => '7',
                'type' => 'integer',
                'description' => 'Durasi maksimal peminjaman peralatan dalam satu pengajuan (hari).',
            ],
            [
                'key' => 'max_active_loans',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Batas maksimal transaksi peminjaman aktif per peminjam.',
            ],
            [
                'key' => 'institution_name',
                'value' => 'SMK Negeri 1 Rekayasa & Multimedia',
                'type' => 'string',
                'description' => 'Nama institusi yang dicetak pada kop laporan resmi.',
            ],
            [
                'key' => 'institution_unit',
                'value' => 'Unit Pelaksana Teknis Laboratorium RPL & Multimedia',
                'type' => 'string',
                'description' => 'Nama unit/laboratorium pengelola inventaris.',
            ],
            [
                'key' => 'institution_address',
                'value' => 'Jl. Pendidikan Teknologi No. 45, Gedung Laboratorium Terpadu Lt. 2',
                'type' => 'string',
                'description' => 'Alamat lengkap institusi pada kop cetak laporan.',
            ],
            [
                'key' => 'institution_phone',
                'value' => '(021) 5589-2410',
                'type' => 'string',
                'description' => 'Nomor telepon resmi laboratorium.',
            ],
        ];

        foreach ($settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                array_merge($item, ['updated_by' => $adminId])
            );
        }
    }
}
