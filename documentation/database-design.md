# 4. Perancangan Basis Data (Database Design)

## 4.1 Spesifikasi Database
- **Nama Database**: `gearhub_db`
- **DBMS**: MariaDB `10.4.32` / MySQL 8+
- **Storage Engine**: `InnoDB` (mendukung transaksi ACID, *Foreign Key Constraints*, dan *Row-Level Locking*)
- **Character Set & Collation**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Tingkat Normalisasi**: **Bentuk Normal Ketiga (3NF)** — setiap atribut non-kunci bergantung penuh hanya pada *Primary Key* tabelnya tanpa ketergantungan transitif. Catatan khusus: kolom `fine_per_day_snapshot` pada tabel `returns` disimpan secara sengaja sebagai *historical audit snapshot* agar perubahan tarif denda di masa depan tidak merusak integritas nilai laporan keuangan masa lalu.

---

## 4.2 Struktur Kamus Data (9 Tabel Utama)

### 1. Tabel `users`
Menyimpan seluruh akun pengguna (Administrator, Petugas, dan Peminjam).

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik pengguna |
| `name` | `VARCHAR(150)` | `NOT NULL`, `INDEX` | Nama lengkap pengguna |
| `username` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | Username unik untuk login |
| `email` | `VARCHAR(150)` | `NOT NULL`, `UNIQUE` | Alamat email unik |
| `identity_number` | `VARCHAR(30)` | `NULLABLE`, `UNIQUE` | Nomor identitas resmi (NIS / NIP) |
| `password` | `VARCHAR(255)` | `NOT NULL` | Hash kata sandi (`Bcrypt`) |
| `role` | `ENUM('admin','petugas','peminjam')` | `NOT NULL`, Default `'peminjam'` | Level hak akses pengguna |
| `phone` | `VARCHAR(25)` | `NULLABLE` | Nomor telepon / WhatsApp |
| `address` | `TEXT` | `NULLABLE` | Kelas / Jabatan / Alamat |
| `avatar` | `VARCHAR(255)` | `NULLABLE` | Path foto profil opsional |
| `is_active` | `TINYINT(1)` | `NOT NULL`, Default `1`, `INDEX(role, is_active)` | Status aktif akun |
| `remember_token` | `VARCHAR(100)` | `NULLABLE` | Token sesi *remember me* |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 2. Tabel `categories`
Menyimpan klasifikasi kategori peralatan inventaris.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik kategori |
| `code` | `VARCHAR(20)` | `NOT NULL`, `UNIQUE` | Kode kategori (contoh: `KAT-KAM`) |
| `name` | `VARCHAR(100)` | `NOT NULL`, `INDEX` | Nama kategori |
| `slug` | `VARCHAR(120)` | `NOT NULL`, `UNIQUE` | Slug URL kategori |
| `description` | `TEXT` | `NULLABLE` | Deskripsi cakupan kategori |
| `is_active` | `TINYINT(1)` | `NOT NULL`, Default `1`, `INDEX` | Status aktif kategori |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 3. Tabel `equipments`
Menyimpan data master peralatan inventaris beserta pembagian kuantitas stok fisik.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik alat |
| `category_id` | `BIGINT UNSIGNED` | `FK -> categories(id)` `RESTRICT` / `CASCADE` | Relasi kategori alat |
| `code` | `VARCHAR(30)` | `NOT NULL`, `UNIQUE` | Kode inventaris unik (`EQP-...`) |
| `name` | `VARCHAR(150)` | `NOT NULL`, `INDEX` | Nama lengkap peralatan |
| `description` | `TEXT` | `NULLABLE` | Spesifikasi & kelengkapan unit |
| `photo` | `VARCHAR(255)` | `NULLABLE` | Path file foto alat di `storage/public` |
| `condition` | `ENUM('baik','rusak_ringan','rusak_berat')` | `NOT NULL`, Default `'baik'`, `INDEX` | Kondisi fisik dominan |
| `qty_total` | `INT UNSIGNED` | `NOT NULL`, Default `1` | Total seluruh unit milik lab |
| `qty_available` | `INT UNSIGNED` | `NOT NULL`, Default `1` | Unit siap dipinjam saat ini |
| `qty_damaged` | `INT UNSIGNED` | `NOT NULL`, Default `0` | Unit rusak berat / dalam servis |
| `location` | `VARCHAR(100)` | `NULLABLE` | Lokasi lemari / rak / ruang lab |
| `status` | `ENUM('tersedia','tidak_tersedia','maintenance')` | `NOT NULL`, Default `'tersedia'`, `INDEX` | Status ketersediaan alat |
| `notes` | `TEXT` | `NULLABLE` | Catatan SOP peminjaman |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 4. Tabel `loans`
Menyimpan header transaksi pengajuan dan peminjaman peralatan.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik transaksi peminjaman |
| `code` | `VARCHAR(30)` | `NOT NULL`, `UNIQUE` | Kode peminjaman (`LN-YYYYMMDD-XXXX`) |
| `user_id` | `BIGINT UNSIGNED` | `FK -> users(id)` `RESTRICT` / `CASCADE` | Peminjam yang mengajukan |
| `loan_date` | `DATE` | `NOT NULL`, `INDEX` | Tanggal rencana mulai pinjam |
| `due_date` | `DATE` | `NOT NULL`, `INDEX(due_date, status)` | Batas tanggal pengembalian |
| `purpose` | `TEXT` | `NOT NULL` | Keperluan kegiatan peminjaman |
| `status` | `ENUM('pending','approved','rejected','borrowed','overdue','returned','cancelled')` | `NOT NULL`, Default `'pending'`, `INDEX` | Status siklus transaksi |
| `approved_by` | `BIGINT UNSIGNED` | `NULLABLE`, `FK -> users(id)` `SET NULL` | Petugas pemberi keputusan |
| `approved_at` | `DATETIME` | `NULLABLE` | Waktu disetujui / ditolak |
| `handed_over_by` | `BIGINT UNSIGNED` | `NULLABLE`, `FK -> users(id)` `SET NULL` | Petugas penyerah fisik alat |
| `handed_over_at` | `DATETIME` | `NULLABLE` | Waktu penyerahan fisik alat |
| `rejection_reason` | `TEXT` | `NULLABLE` | Alasan penolakan pengajuan |
| `notes` | `TEXT` | `NULLABLE` | Catatan tambahan transaksi |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 5. Tabel `loan_details`
Menyimpan rincian peralatan dan kuantitas pada setiap transaksi peminjaman.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID detail item pinjaman |
| `loan_id` | `BIGINT UNSIGNED` | `FK -> loans(id)` `CASCADE`, `UNIQUE(loan_id, equipment_id)` | Header peminjaman |
| `equipment_id` | `BIGINT UNSIGNED` | `FK -> equipments(id)` `RESTRICT` | Peralatan yang dipinjam |
| `qty` | `INT UNSIGNED` | `NOT NULL` | Jumlah unit yang dipinjam |
| `condition_before` | `ENUM('baik','rusak_ringan')` | `NOT NULL`, Default `'baik'` | Kondisi awal saat dipinjam |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 6. Tabel `returns`
Menyimpan header pengajuan pengembalian, verifikasi petugas, dan perhitungan denda.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik pengembalian |
| `code` | `VARCHAR(30)` | `NOT NULL`, `UNIQUE` | Kode pengembalian (`RTN-YYYYMMDD-XXXX`) |
| `loan_id` | `BIGINT UNSIGNED` | `UNIQUE`, `FK -> loans(id)` `RESTRICT` | Relasi 1-ke-1 dengan `loans` |
| `returned_by` | `BIGINT UNSIGNED` | `FK -> users(id)` `RESTRICT` | Pengguna yang mengembalikan |
| `verified_by` | `BIGINT UNSIGNED` | `NULLABLE`, `FK -> users(id)` `SET NULL` | Petugas pemeriksa fisik |
| `return_date` | `DATE` | `NOT NULL`, `INDEX` | Tanggal pengembalian aktual |
| `verified_at` | `DATETIME` | `NULLABLE` | Waktu selesai diverifikasi |
| `late_days` | `INT UNSIGNED` | `NOT NULL`, Default `0` | Jumlah hari keterlambatan |
| `fine_per_day_snapshot` | `DECIMAL(12,2)` | `NOT NULL`, Default `0.00` | Snapshot tarif denda saat verifikasi |
| `fine_amount` | `DECIMAL(12,2)` | `NOT NULL`, Default `0.00` | Total nominal denda (Rp) |
| `fine_paid` | `TINYINT(1)` | `NOT NULL`, Default `0`, `INDEX` | Status lunas pembayaran denda |
| `status` | `ENUM('pending_verification','verified')` | `NOT NULL`, Default `'pending_verification'`, `INDEX` | Status proses pengembalian |
| `notes` | `TEXT` | `NULLABLE` | Catatan pengembalian |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 7. Tabel `return_details`
Menyimpan hasil pemeriksaan fisik per item alat yang dikembalikan.

| Kolom | Tipe Data | Constraint / Indeks | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID detail pengembalian |
| `return_id` | `BIGINT UNSIGNED` | `FK -> returns(id)` `CASCADE` | Header pengembalian |
| `loan_detail_id` | `BIGINT UNSIGNED` | `UNIQUE`, `FK -> loan_details(id)` `RESTRICT` | Relasi 1-ke-1 dengan item pinjam |
| `equipment_id` | `BIGINT UNSIGNED` | `FK -> equipments(id)` `RESTRICT` | Peralatan yang diperiksa |
| `qty_returned` | `INT UNSIGNED` | `NOT NULL` | Jumlah unit dikembalikan |
| `qty_damaged` | `INT UNSIGNED` | `NOT NULL`, Default `0` | Jumlah unit rusak berat (masuk servis) |
| `condition_after` | `ENUM('baik','rusak_ringan','rusak_berat')` | `NOT NULL`, Default `'baik'`, `INDEX` | Kondisi akhir hasil pemeriksaan |
| `damage_notes` | `TEXT` | `NULLABLE` | Catatan detail kerusakan |
| `created_at`, `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu pembuatan & pembaruan |

### 8. Tabel `activity_logs` & 9. Tabel `settings`
- **`activity_logs`**: Menyimpan `id`, `user_id` (`FK SET NULL`), `module`, `action`, `reference_type`, `reference_id`, `description`, `old_values` (`JSON`), `new_values` (`JSON`), `ip_address`, `user_agent`, dan timestamps.
- **`settings`**: Menyimpan pasangan konfigurasi `key` (`UNIQUE`), `value`, `type`, `description`, dan `updated_by` (`FK SET NULL`).
