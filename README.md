# GearHub — Sistem Manajemen Peminjaman Peralatan Multimedia & Laboratorium

Aplikasi berbasis web untuk mengelola inventaris peralatan multimedia dan laboratorium, alur pengajuan peminjaman, verifikasi persetujuan & penyerahan oleh petugas, pemeriksaan kondisi pengembalian, perhitungan denda keterlambatan otomatis, audit log, dan pelaporan resmi.

Proyek ini disusun untuk memenuhi seluruh spesifikasi **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak — Paket 1 (`KM25.4.1.1`)**.

---

## Fitur Utama Berdasarkan Role

- **Administrator (`admin`)**:
  - Dashboard analitik inventaris, grafik tren peminjaman 6 bulan terakhir, distribusi per kategori, dan pemantauan audit.
  - CRUD Manajemen Pengguna (lengkap dengan aktivasi/nonaktivasi akun).
  - CRUD Kategori Alat & CRUD Inventaris Peralatan (unggah foto, lokasi penyimpanan, serta pemulihan unit selesai servis dari `maintenance` ke stok tersedia).
  - Pengelolaan penuh transaksi peminjaman & pengembalian.
  - Log Aktivitas Pengguna & Audit Trigger Database (`db_trigger`).
  - Pengaturan Sistem & Tarif Denda Harian (default `Rp5.000/hari`).
  - Pusat Laporan (7 jenis laporan dengan fitur filter, cetak langsung, unduh PDF, dan ekspor Excel `.xlsx`).
- **Petugas Laboratorium (`petugas`)**:
  - Meja Kerja Antrian Operasional (*Menunggu Persetujuan*, *Siap Diserahkan*, *Verifikasi Pengembalian*, *Terlambat*).
  - Persetujuan pengajuan (`sp_approve_loan`), penolakan wajib beralasan, dan pencatatan penyerahan fisik alat.
  - Pemeriksaan kondisi fisik alat saat kembali (`baik`, `rusak_ringan`, `rusak_berat`) dan verifikasi denda (`fn_calculate_fine` & `sp_process_return`).
  - Pemantauan kondisi & stok alat serta pencetakan laporan.
- **Peminjam (`peminjam`)**:
  - Katalog peralatan laboratorium aktif dengan indikator stok tersedia *real-time*.
  - Pengajuan peminjaman multi-alat, pelacakan status pengajuan, pengingat jatuh tempo, pembatalan pengajuan `pending`, dan pengajuan pengembalian alat.

---

## Persyaratan Lingkungan (Environment)
- PHP `>= 8.3` (ekstensi `pdo_mysql`, `gd`, `zip`, `mbstring`, `xml` aktif)
- Composer `2.x`
- Node.js `>= 20` & npm
- XAMPP MySQL / MariaDB `10.4+` pada port `3306`

---

## Panduan Instalasi & Menjalankan Aplikasi

1. **Nyalakan MySQL dan Web Server**
   Buka **XAMPP Control Panel**, lalu klik **Start** pada **MySQL** (dan **Apache** bila diperlukan) hingga indikator berwarna hijau.

2. **Konfigurasi Environment**
   Pastikan file `.env` mengarah ke database `gearhub_db`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=gearhub_db
   DB_USERNAME=root
   DB_PASSWORD=
   APP_TIMEZONE=Asia/Jakarta
   ```

3. **Buat Database, Jalankan Migrasi (Tabel + Stored Procedure + Function + Trigger) & Seeder**
   ```powershell
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```
   *(Alternatif: Anda juga dapat mengimpor langsung file `database/sql/gearhub_db.sql` melalui phpMyAdmin).*

4. **Build Aset Frontend**
   ```powershell
   npm run build
   ```

5. **Jalankan Server Aplikasi**
   ```powershell
   php artisan serve
   ```
   Buka `http://localhost:8000` pada browser.

---

## Akun Pengujian Default (Password: `password123`)

| Role | Username | Email | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@gearhub.sch.id` | Kepala Laboratorium Terpadu |
| **Petugas Lab** | `petugas` | `petugas@gearhub.sch.id` | Teknisi Lab RPL & Jaringan |
| **Petugas Studio** | `petugas.mm` | `petugas.mm@gearhub.sch.id` | Teknisi Studio Multimedia |
| **Peminjam (Siswa)** | `peminjam` | `budi.santoso@siswa.gearhub.sch.id` | Kelas XII RPL 1 |
| **Peminjam (Siswa)** | `siti.aisyah` | `siti.aisyah@siswa.gearhub.sch.id` | Kelas XII DKV 2 |

---

## Menjalankan Pengujian Otomatis (14 Skenario UKK)
Pastikan database uji `gearhub_test` tersedia di MySQL, lalu jalankan:
```powershell
php artisan test
```

---

## Daftar Dokumentasi UKK (`documentation/`)
Seluruh dokumen teknis untuk penilaian sidang UKK tersedia lengkap di dalam folder [`documentation/`](./documentation):
1. `analisis-kebutuhan.md`
2. `deskripsi-program.md`
3. `arsitektur-aplikasi.md`
4. `database-design.md`
5. `dokumentasi-erd.md`
6. `flowchart-login.md`
7. `flowchart-peminjaman.md`
8. `flowchart-pengembalian.md`
9. `dokumentasi-fungsi-prosedur.md`
10. `dokumentasi-database-object.md`
11. `dokumentasi-debugging.md`
12. `pengujian-aplikasi.md`
13. `laporan-evaluasi.md`
