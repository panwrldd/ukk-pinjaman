<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $users = [
            [
                'name' => 'Hendra Wijaya, S.Kom.',
                'username' => 'admin',
                'email' => 'admin@gearhub.sch.id',
                'identity_number' => '198804122014021001',
                'password' => $password,
                'role' => 'admin',
                'phone' => '081288001101',
                'address' => 'Kepala Laboratorium Terpadu RPL & Multimedia',
                'is_active' => true,
            ],
            [
                'name' => 'Dimas Saputra, A.Md.T.',
                'username' => 'petugas',
                'email' => 'petugas@gearhub.sch.id',
                'identity_number' => '199308192019031004',
                'password' => $password,
                'role' => 'petugas',
                'phone' => '081288001102',
                'address' => 'Teknisi Laboratorium RPL & Jaringan',
                'is_active' => true,
            ],
            [
                'name' => 'Larasati Kusuma, S.Ds.',
                'username' => 'petugas.mm',
                'email' => 'petugas.mm@gearhub.sch.id',
                'identity_number' => '199603112022212008',
                'password' => $password,
                'role' => 'petugas',
                'phone' => '081288001103',
                'address' => 'Teknisi Studio Multimedia & Fotografi',
                'is_active' => true,
            ],
            [
                'name' => 'Budi Santoso',
                'username' => 'peminjam',
                'email' => 'budi.santoso@siswa.gearhub.sch.id',
                'identity_number' => '24251001',
                'password' => $password,
                'role' => 'peminjam',
                'phone' => '085712340001',
                'address' => 'Kelas XII RPL 1',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Aisyah Putri',
                'username' => 'siti.aisyah',
                'email' => 'siti.aisyah@siswa.gearhub.sch.id',
                'identity_number' => '24251002',
                'password' => $password,
                'role' => 'peminjam',
                'phone' => '085712340002',
                'address' => 'Kelas XII DKV 2',
                'is_active' => true,
            ],
            [
                'name' => 'Rizky Pratama',
                'username' => 'rizky.pratama',
                'email' => 'rizky.pratama@siswa.gearhub.sch.id',
                'identity_number' => '24251003',
                'password' => $password,
                'role' => 'peminjam',
                'phone' => '085712340003',
                'address' => 'Kelas XI TJKT 1',
                'is_active' => true,
            ],
            [
                'name' => 'Nadia Maharani',
                'username' => 'nadia.maharani',
                'email' => 'nadia.maharani@siswa.gearhub.sch.id',
                'identity_number' => '24251004',
                'password' => $password,
                'role' => 'peminjam',
                'phone' => '085712340004',
                'address' => 'Kelas XII RPL 2',
                'is_active' => true,
            ],
            [
                'name' => 'Fajar Nugroho',
                'username' => 'fajar.nonaktif',
                'email' => 'fajar.nugroho@siswa.gearhub.sch.id',
                'identity_number' => '23241099',
                'password' => $password,
                'role' => 'peminjam',
                'phone' => '085712340099',
                'address' => 'Alumni / Akun Nonaktif',
                'is_active' => false,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                $user
            );
        }
    }
}
