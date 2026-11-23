# 1. Analisis Kebutuhan Sistem — GearHub

## 1.1 Latar Belakang Masalah
Pengelolaan peminjaman peralatan multimedia dan laboratorium (kamera, mikrofon wireless, proyektor, perangkat jaringan, dan perlengkapan studio) di lingkungan Sekolah Menengah Kejuruan (SMK) sering mengalami kendala apabila masih dicatat secara manual pada buku log kertas, antara lain:
1. Sulit mengetahui jumlah stok unit yang benar-benar siap dipinjam secara *real-time*.
2. Risiko bentrok jadwal peminjaman atau kehilangan jejak penanggung jawab peminjaman.
3. Keterlambatan pengembalian dan perhitungan denda yang tidak konsisten.
4. Unit yang dikembalikan dalam kondisi rusak berat tercampur kembali dengan stok layak pakai.
5. Kesulitan menyusun laporan periodik untuk kepala laboratorium maupun audit sekolah.

**GearHub** dibangun menggunakan metode **Waterfall Terstruktur** sesuai ketentuan **Soal Praktik Kejuruan UKK RPL Paket 1 (`KM25.4.1.1`)** untuk menyelesaikan seluruh permasalahan tersebut secara terintegrasi.

---

## 1.2 Identifikasi Pengguna (Aktor) dan Hak Akses

Sistem memiliki **3 (tiga) level pengguna** dengan pembatasan hak akses di sisi server (*Backend Role-Based Access Control*):

### A. Administrator (`admin`)
- Mengelola seluruh data master: Pengguna (`users`), Kategori Alat (`categories`), dan Inventaris Alat (`equipments`).
- Memulihkan stok peralatan yang selesai diperbaiki dari status `maintenance` / `qty_damaged` ke stok tersedia (`qty_available`).
- Mengelola data transaksi peminjaman dan pengembalian.
- Memantau log aktivitas pengguna dan jejak audit trigger database (`activity_logs`).
- Mengatur kebijakan sistem, termasuk **tarif denda keterlambatan per hari** (`settings`).
- Melihat pratinjau, mencetak, mengunduh PDF, dan mengekspor Excel seluruh 7 jenis laporan.

### B. Petugas Laboratorium (`petugas`)
- Memantau antrian pengajuan peminjaman baru (`pending`).
- Memeriksa detail pengajuan dan memutuskan **Menyetujui** (memicu pemotongan stok reservasi via `sp_approve_loan`) atau **Menolak** disertai alasan wajib.
- Mencatat penyerahan fisik peralatan kepada peminjam (`borrowed`).
- Memantau pengembalian alat, memeriksa kondisi fisik setiap item (`baik`, `rusak_ringan`, `rusak_berat`), serta memverifikasi pengembalian & denda (`sp_process_return` dan `fn_calculate_fine`).
- Memantau kondisi dan ketersediaan inventaris alat.
- Melihat dan mencetak laporan peminjaman, pengembalian, keterlambatan, denda, dan inventaris.

### C. Peminjam (`peminjam` — Siswa / Guru)
- Melihat katalog peralatan aktif, mencari alat, memfilter kategori, dan melihat detail spesifikasi serta ketersediaan stok.
- Mengajukan peminjaman satu atau beberapa peralatan sekaligus dengan menentukan jumlah unit, tanggal pinjam, tanggal kembali, dan keperluan kegiatan.
- Memantau status pengajuan (`pending`, `approved`, `rejected`, `borrowed`, `overdue`, `returned`) dan membatalkan pengajuan selama masih berstatus `pending`.
- Melihat jadwal batas waktu pengembalian dan mengajukan proses pengembalian alat.

---

## 1.3 Kebutuhan Fungsional (Input — Proses — Output per Modul)

| Kode | Modul | Input | Proses Utama | Output |
| :--- | :--- | :--- | :--- | :--- |
| **MF-01** | Autentikasi | `identity` (username/email), `password` | Validasi input, cek rate-limiter, verifikasi hash bcrypt, cek `is_active`, regenerasi session, catat `activity_logs` | Redirect ke dashboard sesuai role atau pesan error |
| **MF-02** | Dashboard per Role | Session role pengguna aktif | Agregasi metrik dinamis dari database, sinkronisasi status `overdue`, query tren bulanan & antrian | Dashboard kontekstual Admin / Petugas / Peminjam |
| **MF-03** | Manajemen Pengguna | Nama, username, email, NIS/NIP, password, role, telepon, alamat, status | Validasi keunikan username/email/identitas, hash password, simpan transaksi & log aktivitas | Data akun tersimpan dan siap digunakan sesuai role |
| **MF-04** | Manajemen Kategori | Kode kategori, nama, deskripsi, status aktif | Generate slug otomatis, validasi kode unik, proteksi hapus jika masih memiliki alat | Klasifikasi kategori inventaris aktif/nonaktif |
| **MF-05** | Manajemen Alat | Kode alat, nama, kategori, foto, kondisi, total stok, lokasi, status, catatan | Validasi invarian stok (`qty_available + qty_damaged <= qty_total`), simpan foto ke `storage/public`, catat log | Data inventaris alat beserta indikator ketersediaan akurat |
| **MF-06** | Peminjaman | Tanggal pinjam & kembali, keperluan, daftar item (`equipment_id`, `qty`) | Validasi kuota & ketersediaan stok (`FOR UPDATE`), simpan `pending`; saat disetujui panggil `CALL sp_approve_loan`; catat penyerahan fisik | Kode transaksi `LN-...`, pembaruan `qty_available`, histori status |
| **MF-07** | Pengembalian & Denda | Pengajuan kembali, kondisi fisik tiap alat, jumlah unit rusak, status bayar denda | Hitung keterlambatan & denda via `fn_count_late_days` & `fn_calculate_fine`; eksekusi `CALL sp_process_return` dalam transaksi | Kode `RTN-...`, denda akurat, pemulihan stok baik / pemisahan stok rusak |
| **MF-08** | Log Aktivitas | Event aksi aplikasi & event perubahan baris di MariaDB | Pencatatan via `ActivityLogService` dan Trigger `AFTER UPDATE` (`trg_loans_status_audit`, `trg_settings_value_audit`) | Rekam jejak audit permanen yang tidak dapat dimanipulasi peminjam |
| **MF-09** | Laporan | Jenis laporan, rentang tanggal, filter kategori/status/kondisi/role | Query relasional terfilter (`Eager Loading`), agregasi ringkasan, render HTML cetak / DomPDF / Maatwebsite Excel | Pratinjau web, lembar cetak resmi, file `.pdf`, dan file `.xlsx` |

---

## 1.4 Kebutuhan Non-Fungsional
1. **Keamanan (Security)**: Password di-hash menggunakan `Bcrypt`, proteksi penuh CSRF token pada setiap form `POST/PUT/PATCH/DELETE`, pencegahan SQL Injection melalui *Prepared Statements* Eloquent & parameter terikat pada `CALL sp_*`, pencegahan XSS melalui escaping otomatis Blade `{{ }}`, serta otorisasi berlapis (`CheckRole`, `EnsureActiveUser`, `LoanPolicy`, `ReturnPolicy`).
2. **Integritas Data (ACID)**: Seluruh operasi mutasi stok dan verifikasi dibungkus dalam transaksi database (`BEGIN`, `COMMIT`, `ROLLBACK`) pada engine `InnoDB` dengan *row-level locking* (`SELECT ... FOR UPDATE`).
3. **Efisiensi Kinerja**: Penggunaan *Eager Loading* (`with(...)`) untuk mencegah masalah *N+1 Query*, pengindeksan kolom kunci pencarian, dan pembatasan baris melalui *Pagination*.
4. **Kenyamanan Antarmuka (Usability)**: Desain bersih berorientasi tugas (*Anti-AI-Slop*), responsif di desktop/tablet/smartphone, ikonografi konsisten dengan Lucide Icons, dan umpan balik instan melalui *toast notification*.
