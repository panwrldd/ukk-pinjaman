# GearHub — Sistem Manajemen Peminjaman Peralatan Multimedia & Laboratorium

Aplikasi berbasis web (**Next.js 15 App Router + TypeScript + Tailwind CSS + Netlify Blobs**) untuk mengelola inventaris peralatan multimedia dan laboratorium, alur pengajuan peminjaman, verifikasi persetujuan & penyerahan oleh petugas, pemeriksaan kondisi pengembalian, perhitungan denda keterlambatan otomatis, audit log, dan pelaporan resmi.

Proyek ini disusun untuk memenuhi seluruh spesifikasi **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak — Paket 1 (`KM25.4.1.1`)** dan telah dioptimalkan agar langsung siap di-deploy ke **Netlify** tanpa konfigurasi database eksternal tambahan.

> **Catatan Arsip Laravel:** Kode sumber versi Laravel + MySQL asli tersimpan utuh pada branch [`laravel-backup`](https://github.com/panwrldd/ukk-pinjaman/tree/laravel-backup).

---

## Fitur Utama Berdasarkan Role

- **Administrator (`admin`)**:
  - Dashboard analitik inventaris, grafik tren peminjaman 6 bulan terakhir, distribusi per kategori, dan pemantauan audit.
  - CRUD Manajemen Pengguna (lengkap dengan aktivasi/nonaktivasi akun).
  - CRUD Kategori Alat & CRUD Inventaris Peralatan (unggah foto, lokasi penyimpanan, serta pemulihan unit selesai servis dari `maintenance` ke stok tersedia).
  - Pengelolaan penuh transaksi peminjaman & pengembalian.
  - Log Aktivitas Pengguna & Audit Perubahan Status Otomatis (`db_trigger`).
  - Pengaturan Sistem, Tarif Denda Harian (default `Rp5.000/hari`), & tombol **Reset Data Demo Awal (Seeder UKK)**.
  - Pusat Laporan (7 jenis laporan dengan fitur filter, cetak langsung, simpan PDF, dan ekspor Excel `.xlsx`).
- **Petugas Laboratorium (`petugas`)**:
  - Meja Kerja Antrian Operasional (*Menunggu Persetujuan*, *Siap Diserahkan*, *Verifikasi Pengembalian*, *Terlambat*).
  - Persetujuan pengajuan, penolakan wajib beralasan, dan pencatatan penyerahan fisik alat.
  - Pemeriksaan kondisi fisik alat saat kembali (`baik`, `rusak_ringan`, `rusak_berat`) dan verifikasi denda keterlambatan otomatis.
  - Pemantauan kondisi & stok alat serta pencetakan laporan.
- **Peminjam (`peminjam`)**:
  - Katalog peralatan laboratorium aktif dengan indikator stok tersedia *real-time*.
  - Pengajuan peminjaman multi-alat, pelacakan status pengajuan, pengingat jatuh tempo, pembatalan pengajuan `pending`, dan pengajuan pengembalian alat.

---

## Deploy Otomatis di Netlify

Proyek ini sudah dilengkapi `netlify.toml` dan `@netlify/blobs`:
1. Hubungkan repositori GitHub ini ke **Netlify** (branch `main`).
2. Netlify akan otomatis menjalankan `npm run build` dengan `@netlify/plugin-nextjs`.
3. Penyimpanan data menggunakan **Netlify Blobs** secara otomatis dan langsung terisi dengan data awal seeder UKK saat pertama kali diakses.

---

## Menjalankan Secara Lokal

```bash
npm install
npm run dev
```

Buka `http://localhost:3000` pada browser (mode lokal otomatis menyimpan data pada `.data/gearhub-ukk-db.json`).

---

## Akun Pengujian Default (Password: `password123`)

| Role | Username | Email | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@gearhub.sch.id` | Kepala Laboratorium Terpadu |
| **Petugas Lab** | `petugas` | `petugas@gearhub.sch.id` | Teknisi Lab RPL & Jaringan |
| **Petugas Studio** | `petugas.mm` | `petugas.mm@gearhub.sch.id` | Teknisi Studio Multimedia |
| **Peminjam (Siswa)** | `peminjam` | `budi.santoso@siswa.gearhub.sch.id` | Kelas XII RPL 1 |
| **Peminjam (Siswa)** | `siti.aisyah` | `siti.aisyah@siswa.gearhub.sch.id` | Kelas XII DKV 2 |
