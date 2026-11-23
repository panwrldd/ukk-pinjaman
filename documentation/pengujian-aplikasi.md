# 12. Dokumentasi Pengujian Aplikasi (Test Cases)

Dokumen ini memuat hasil eksekusi pengujian fungsional, keamanan hak akses (*RBAC*), validasi input, objek basis data (*Stored Procedure, Function, Trigger*), dan integritas transaksi (*Rollback*) sesuai ketentuan soal **UKK RPL Paket 1 (`KM25.4.1.1`)**.

Seluruh pengujian telah dijalankan secara nyata terhadap **PHP `8.3.31`** dan **MariaDB `10.4.32`** menggunakan suite pengujian otomatis [`tests/Feature/GearHubUkkTest.php`](../tests/Feature/GearHubUkkTest.php).

---

## 12.1 Ringkasan Eksekusi Pengujian Otomatis & Build

```text
PHPUnit 12.5.43 — Runtime: PHP 8.3.31 | Database: MariaDB 10.4.32 (InnoDB)
Time: 00:14.021, Memory: 56.00 MB

OK (16 tests, 80 assertions, 0 failures, 0 errors)
```

| ID | Pemeriksaan Kompilasi & Infrastruktur | Perintah Eksekusi | Hasil Aktual | Status |
| :--- | :--- | :--- | :--- | :---: |
| **BUILD-01** | Pemeriksaan Sintaks Seluruh File PHP | `php -l` | `0 syntax errors detected` | **PASS** |
| **BUILD-02** | Resolusi Routing, Middleware & Controller | `php artisan route:list --except-vendor` | `66 routes` terdaftar tanpa konflik | **PASS** |
| **BUILD-03** | Kompilasi Seluruh 35 Template Blade | `php artisan view:cache` | `Blade templates cached successfully` | **PASS** |
| **BUILD-04** | Bundling Aset Produksi CSS Tailwind v4 & Alpine.js | `npm run build` | `built in 338ms` (`manifest.json`, CSS & JS) | **PASS** |
| **DB-01** | Migrasi Tabel, Stored Procedure, Function, Trigger & Seeder | `php artisan migrate:fresh --seed` | `12 migrations` & `5 seeders` sukses (`0 error`) | **PASS** |
| **DB-02** | Ekspor Skema, Data, Routines & Triggers | `mysqldump --routines --triggers` | `database/sql/gearhub_db.sql` terbentuk utuh | **PASS** |

---

## 12.2 Matriks Hasil Eksekusi 14 Skenario Pengujian Aplikasi (`GearHubUkkTest`)

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual (MariaDB 10.4.32) | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Login kredensial benar (username & email), salah, dan akun nonaktif | 1. Kirim password salah.<br>2. Login dengan akun `is_active = 0`.<br>3. Login dengan username `admin.uji` dan email `siswa.uji@...`. | Kredensial salah & nonaktif ditolak dengan pesan error; login valid diarahkan ke `/admin/dashboard` & `/borrower/dashboard`. | Password salah & akun nonaktif ditolak; sesi terbentuk & redirect sesuai role. | **PASS** |
| **TC-02** | Admin menambahkan peralatan baru beserta foto | Login sebagai Admin, kirim `POST /admin/equipments` beserta unggahan foto `.jpg` dan stok `4`. | Record tersimpan di `equipments`, file foto tersimpan di `storage/public/equipments`. | Record `EQP-UJI-002` tersimpan dengan `qty_total = 4`, `qty_available = 4`, foto valid. | **PASS** |
| **TC-03** | Peminjam mengajukan peminjaman alat | Login sebagai Peminjam, kirim `POST /borrower/loans` untuk 2 unit kamera. | Transaksi `loans` berstatus `pending` terbentuk, stok belum berkurang sebelum disetujui. | Transaksi terbentuk (`status = pending`), `qty_available` tetap `5` hingga disetujui petugas. | **PASS** |
| **TC-04** | Pengembalian tepat waktu (`Rp0`) & terlambat (`fn_calculate_fine`) | 1. Verifikasi kembali tepat pada `due_date`.<br>2. Verifikasi kembali terlambat 4 hari (tarif Rp5.000/hari). | Tepat waktu menghasilkan `Rp0`; terlambat 4 hari menghasilkan `Rp20.000` dan stok pulih. | Tepat waktu: `late_days = 0`, `fine_amount = 0.00`; Terlambat 4 hari: `fine_amount = 20000.00`, stok kembali `5`. | **PASS** |
| **TC-05** | Pengujian hak akses masing-masing role (RBAC & Policy) | 1. Peminjam akses `/admin/dashboard`, `/staff/dashboard`, `/reports`.<br>2. Petugas akses `/admin/users` & laporan `users`.<br>3. Peminjam akses detail pinjaman siswa lain. | Seluruh percobaan akses tanpa hak ditolak dengan kode HTTP `403 Forbidden`. | Seluruh endpoint terlarang mengembalikan HTTP `403`, sedangkan endpoint sah mengembalikan `200`. | **PASS** |
| **TC-06** | Validasi pengajuan melebihi stok tersedia | Peminjam mengajukan 10 unit pada alat yang hanya memiliki `qty_available = 5`. | Sistem menolak pengajuan, me-rollback transaksi, dan menampilkan pesan stok tidak cukup. | Pengajuan ditolak dengan pesan error sesi dan jumlah baris `loans` tetap `0`. | **PASS** |
| **TC-07** | Pencegahan pengajuan pending ganda | Peminjam mengirim pengajuan baru untuk alat yang sama saat pengajuan sebelumnya masih `pending`. | Sistem menolak duplikasi pengajuan `pending` dengan `DomainException`. | Eksepsi validasi tertangkap dan duplikasi baris berhasil dicegah. | **PASS** |
| **TC-08** | Persetujuan peminjaman via `sp_approve_loan` & trigger audit | Petugas menyetujui pengajuan 2 unit dari stok awal 5 unit. | `qty_available` menjadi `3` (terpotong tepat 1 kali) dan `trg_loans_status_audit` mencatat ke `activity_logs`. | `qty_available = 3`, status `approved`, dan baris `module = db_trigger` tercatat otomatis. | **PASS** |
| **TC-09** | Penolakan pengajuan wajib beralasan | 1. Kirim penolakan tanpa alasan.<br>2. Kirim penolakan dengan alasan lengkap. | Tanpa alasan ditolak validasi; dengan alasan status menjadi `rejected` dan stok tetap `5`. | Validasi `rejection_reason` berjalan; saat diisi status berubah ke `rejected` & stok tetap `5`. | **PASS** |
| **TC-10** | Deteksi otomatis peminjaman terlambat (`overdue`) | Jalankan `LoanService::syncOverdueStatuses()` pada pinjaman `borrowed` yang melewati `due_date`. | Status pinjaman berubah menjadi `overdue`. | Kolom `loans.status` terbarui menjadi `overdue` secara akurat. | **PASS** |
| **TC-11** | Pengembalian alat `rusak_berat` & pemulihan servis oleh Admin | 1. Verifikasi kembali 2 unit (1 baik, 1 rusak berat).<br>2. Admin jalankan `restore-maintenance` 1 unit. | Unit rusak masuk ke `qty_damaged = 1` (**tidak** masuk `qty_available`); setelah dipulihkan `qty_available` kembali `5`. | Saat kembali: `qty_available = 4`, `qty_damaged = 1`, sisa unit tetap `tersedia`; usai dipulihkan: `qty_available = 5`, `qty_damaged = 0`. | **PASS** |
| **TC-12** | Perubahan tarif denda oleh Admin & audit trigger | Admin mengubah tarif denda dari `Rp5.000` ke `Rp10.000`. | `trg_settings_value_audit` mencatat perubahan nilai, `fn_calculate_fine` menghitung tarif baru. | Log trigger `setting_updated` tercatat; simulasi terlambat 2 hari di `fn_calculate_fine` menghasilkan `Rp20.000`. | **PASS** |
| **TC-13** | Validasi duplikasi akun & tanggal kembali tidak valid | 1. Buat user dengan username/email yang sudah ada.<br>2. Buat pinjaman dengan `due_date < loan_date`. | Form Request menolak kedua request dengan pesan error spesifik per field. | Error validasi sesi muncul tepat pada field `username`, `email`, dan `due_date`. | **PASS** |
| **TC-14** | Database Rollback & Trigger penjaga stok (`trg_equipments_stock_guard`) | Buka transaksi, ubah lokasi alat, lalu paksa `qty_available = 99` (> `qty_total = 5`). | Trigger melempar `SIGNAL SQLSTATE '45000'`, blok `catch` menjalankan `ROLLBACK`, lokasi kembali seperti semula. | Pesan `Integritas stok gagal` tertangkap dan `ROLLBACK` membatalkan perubahan lokasi di awal transaksi. | **PASS** |
