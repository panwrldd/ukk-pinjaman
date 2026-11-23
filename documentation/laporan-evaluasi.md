# 13. Laporan Evaluasi Pengembangan Aplikasi — GearHub

Sesuai ketentuan Langkah Kerja poin 11 pada dokumen soal **UKK RPL Paket 1 (`KM25.4.1.1`)**, berikut evaluasi akhir hasil pembangunan dan pengujian aplikasi **GearHub**.

---

## 13.1 Fitur yang Sudah Berjalan dengan Baik (Terverifikasi 100% PASS)
1. **Autentikasi & Keamanan Role (RBAC)**:
   - Login fleksibel menggunakan `username` maupun `email`, *rate limiting* anti *brute-force*, validasi status akun aktif secara *real-time* (`EnsureActiveUser`), middleware `CheckRole`, serta otorisasi kepemilikan transaksi (`LoanPolicy` dan `ReturnPolicy`).
2. **Tiga Ruang Kerja Kontekstual Berbeda Sesuai Role**:
   - **Dashboard Admin**: Pusat analitik inventaris, komposisi posisi fisik unit, grafik tren 6 bulan berbasis database, distribusi kategori, peringatan keterlambatan, pemulihan unit *maintenance*, dan umpan audit sistem/trigger.
   - **Dashboard Petugas**: Meja antrian operasional bertab (*Menunggu Persetujuan*, *Siap Diserahkan*, *Verifikasi Pengembalian*, *Terlambat*) untuk percepatan pelayanan loket laboratorium.
   - **Dashboard Peminjam**: Portal mandiri dengan pengingat batas waktu pengembalian, status pengajuan aktif, dan etalase katalog alat siap pinjam.
3. **Manajemen Master Data (Pengguna, Kategori, Inventaris Alat)**:
   - Dilengkapi pencarian terindeks, multi-filter, *pagination*, unggah foto alat, validasi invarian stok, serta proteksi integritas referensial (`ON DELETE RESTRICT`).
4. **Siklus Transaksi Peminjaman, Pengembalian & Perhitungan Denda**:
   - Mendukung peminjaman multi-item dalam satu kode transaksi, pemotongan stok atomik tepat satu kali via `sp_approve_loan`, pencatatan penyerahan fisik, kalkulasi keterlambatan & denda otomatis via `fn_count_late_days` dan `fn_calculate_fine`, serta pemisahan stok `rusak_berat` ke `qty_damaged` via `sp_process_return`.
5. **Database Objects & Transaksi ACID pada MariaDB 10.4.32**:
   - Seluruh 2 *Stored Functions*, 2 *Stored Procedures*, 4 *Triggers* (`BEFORE INSERT/UPDATE` penjaga stok dan `AFTER UPDATE` pencatat audit), serta transaksi `BEGIN / COMMIT / ROLLBACK` telah diuji langsung dengan hasil **`16 tests, 80 assertions — PASS`**.
6. **Pusat Laporan Multi-Format**:
   - Menyediakan 7 jenis laporan aktual dengan pratinjau web, tampilan cetak kop resmi institusi, unduhan PDF (`DomPDF`), dan ekspor spreadsheet Excel `.xlsx` (`Maatwebsite Excel`).

---

## 13.2 Bug yang Belum Diperbaiki / Keterbatasan Saat Ini
- **Tidak terdapat bug fungsional maupun error sintaks yang tersisa** pada lingkup spesifikasi wajib UKK (`0 errors` pada PHP Lint, Blade Compilation, Vite Build, maupun 16 pengujian otomatis PHPUnit).
- **Keterbatasan ruang lingkup saat ini**: Pemberitahuan pengingat jatuh tempo (`overdue`) masih bersifat *in-app alert* di dalam dashboard aplikasi dan belum terhubung ke pengiriman pesan eksternal otomatis (WhatsApp / SMTP Email), karena sistem dirancang agar 100% mandiri di jaringan lokal ruang ujian tanpa bergantung pada API pihak ketiga.

---

## 13.3 Rencana Pengembangan Berikutnya (Future Roadmap)
1. **Integrasi Pemindaian QR Code / Barcode Inventaris**:
   - Mencetak label QR Code pada setiap unit fisik peralatan agar Petugas cukup memindai kode menggunakan kamera/scanner saat penyerahan (`hand-over`) maupun saat verifikasi pengembalian di loket.
2. **Penjadwalan Booking Kalender (Reservasi Tanggal Mendatang)**:
   - Menampilkan kalender ketersediaan per jam pelajaran untuk ruang studio dan kamera utama agar jadwal praktikum antar jurusan tidak berbenturan.
3. **Notifikasi Pengingat Otomatis (Email / WhatsApp H-1 Jatuh Tempo)**:
   - Menambahkan Laravel Scheduler (`cron`) untuk mengirimkan pengingat pengembalian sehari sebelum jatuh tempo guna menekan angka keterlambatan siswa.
4. **Cetak Slip Bukti Serah Terima (Berita Acara Peminjaman)**:
   - Menambahkan cetak slip tanda terima ringkas ukuran A5 saat alat diserahkan maupun selesai dikembalikan.
