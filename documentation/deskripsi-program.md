# 2. Deskripsi Program — GearHub

## 2.1 Identitas Aplikasi
- **Nama Aplikasi**: GearHub — Sistem Manajemen Peminjaman Peralatan Multimedia dan Laboratorium
- **Konteks Proyek**: Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak — Paket 1 (`KM25.4.1.1`)
- **Arsitektur**: Web Application berbasis *Layered MVC + Service Pattern*
- **Zona Waktu Sistem**: `Asia/Jakarta` (WIB)

## 2.2 Spesifikasi Teknologi
| Lapisan | Teknologi yang Digunakan | Fungsi Utama |
| :--- | :--- | :--- |
| **Bahasa Backend** | PHP `8.3.31` | Eksekusi logika bisnis server-side |
| **Framework Utama** | Laravel `13.40.0` | Routing, ORM Eloquent, Middleware, Policy, Validation, Session |
| **DBMS** | MariaDB `10.4.32` (XAMPP MySQL Port `3306`) | Penyimpanan relasional `InnoDB`, Stored Procedure, Function, Trigger |
| **Template Engine** | Laravel Blade Components | Rendering antarmuka modular dan aman dari XSS |
| **Styling & UI** | Tailwind CSS `v4` + Lucide Icons SVG | Desain antarmuka profesional Navy/Slate yang responsif |
| **Interaktivitas Client** | Alpine.js `v3.17` | State modal, tab antrian petugas, form multi-item, dan toast |
| **Generator Dokumen** | `barryvdh/laravel-dompdf` & `maatwebsite/excel` | Pencetakan laporan PDF resmi dan ekspor spreadsheet `.xlsx` |

---

## 2.3 Cara Kerja Utama Aplikasi
1. **Autentikasi Terpadu**: Pengguna masuk melalui halaman `/login` menggunakan `username` atau `email` beserta kata sandi. Sistem memeriksa status aktif akun (`is_active`) dan mengarahkan pengguna secara otomatis ke ruang kerja masing-masing:
   - Admin &rarr; `/admin/dashboard`
   - Petugas &rarr; `/staff/dashboard`
   - Peminjam &rarr; `/borrower/dashboard`
2. **Siklus Peminjaman Terkendali**:
   - Peminjam memilih peralatan yang tersedia di katalog dan mengirim pengajuan (`status = pending`). Pada tahap ini stok gudang belum dikurangi agar pengajuan yang belum diverifikasi tidak mengunci inventaris.
   - Petugas memeriksa detail pengajuan. Saat Petugas menekan **Setujui**, aplikasi memanggil prosedur tersimpan `sp_approve_loan` di dalam transaksi database untuk memvalidasi ulang ketersediaan stok secara terkunci (`FOR UPDATE`), mengurangi `qty_available` tepat satu kali, dan mengubah status menjadi `approved`.
   - Saat peminjam mengambil alat di loket, Petugas mencatat penyerahan fisik (`status = borrowed`, mencatat waktu `handed_over_at` dan petugas `handed_over_by`).
3. **Pemantauan Jatuh Tempo & Pengembalian**:
   - Apabila tanggal hari ini melewati `due_date` pada pinjaman berstatus `borrowed`, sistem otomatis menandainya sebagai `overdue`.
   - Peminjam mengajukan pengembalian (`returns.status = pending_verification`) dan menyerahkan fisik peralatan ke loket laboratorium.
   - Petugas memeriksa kondisi fisik setiap unit alat (`baik`, `rusak_ringan`, atau `rusak_berat`). Sistem memanggil `sp_process_return` yang di dalamnya menggunakan fungsi `fn_count_late_days` dan `fn_calculate_fine`:
     - Jika terlambat, denda dihitung otomatis sebesar $\text{Hari Terlambat} \times \text{Tarif Denda per Hari}$ (tarif awal `Rp5.000/hari` dari tabel `settings`).
     - Unit berkondisi `baik` atau `rusak_ringan` dikembalikan ke `qty_available`.
     - Unit berkondisi `rusak_berat` dimasukkan ke `qty_damaged` (**tidak dikembalikan ke `qty_available`**) hingga dinyatakan layak pakai kembali oleh Admin melalui fitur **Pemulihan Unit Selesai Perbaikan**.
4. **Audit & Pelaporan**:
   - Seluruh aksi penting di aplikasi serta perubahan status di level tabel database tercatat otomatis pada tabel `activity_logs`.
   - Admin dan Petugas dapat memfilter, meninjau, mencetak langsung, mengunduh PDF, dan mengekspor Excel laporan aktual kapan saja.
