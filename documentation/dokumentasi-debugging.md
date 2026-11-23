# 11. Dokumentasi Debugging & Penyelesaian Masalah Teknis

Dokumen ini mencatat temuan *bug* dan kendala teknis nyata yang ditemukan selama proses inspeksi kode awal, pembangunan aplikasi, dan kompilasi aset beserta akar masalah (*root cause*) dan solusi perbaikannya.

---

## Kasus Debugging 1 — Eloquent Uncountable Noun Inflector (`equipment` vs `equipments`)
- **Gejala / Potensi Error**:
  Query Eloquent `Equipment::all()` mengarah ke tabel bernama `equipment` (tanpa huruf `s`), sehingga memicu `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'gearhub_db.equipment' doesn't exist` karena migrasi membuat tabel bernama `equipments`.
- **Akar Masalah (Root Cause)**:
  Dalam aturan tata bahasa Inggris pada *Laravel Pluralizer*, kata *"equipment"* merupakan *uncountable noun* sehingga bentuk jamaknya tetap `"equipment"`. Pada kode model awal belum didefinisikan properti `$table`.
- **Solusi Perbaikan**:
  Menambahkan deklarasi eksplisit `protected $table = 'equipments';` pada `app/Models/Equipment.php` serta mendefinisikan foreign key `'equipment_id'` secara eksplisit pada seluruh relasi `hasMany` dan `belongsTo`.

---

## Kasus Debugging 2 — Kebocoran Stok (*Stock Leak*) Saat Pembatalan Peminjaman & Penguncian Seluruh Unit Saat 1 Unit Rusak
- **Gejala / Masalah Logika Bisnis**:
  1. Pada `LoanService` awal, peminjam diizinkan membatalkan peminjaman saat berstatus `disetujui` (setelah stok dikurangi prosedur), namun fungsi pembatalan hanya mengubah status menjadi `dibatalkan` tanpa mengembalikan `qty_available`. Akibatnya stok berkurang permanen secara siluman.
  2. Pada `ReturnService` awal, ketika 1 unit dari peralatan berstok 5 unit dikembalikan dalam kondisi `rusak_berat`, seluruh record peralatan diubah menjadi `status = 'maintenance'`, menyebabkan 4 unit lain yang masih baik ikut terkunci tidak dapat dipinjam.
- **Akar Masalah (Root Cause)**:
  Belum dipisahkannya kolom penghitung stok rusak (`qty_damaged`) dari `qty_available` dan belum dibedakannya hak pembatalan antara status `pending` dan `approved`.
- **Solusi Perbaikan**:
  1. Membatasi peminjam hanya boleh membatalkan saat status `pending` (sebelum stok dikurangi), sedangkan pembatalan status `approved` oleh petugas wajib mengembalikan `qty_available` di dalam transaksi database.
  2. Menambahkan kolom `qty_damaged` pada tabel `equipments` dan memprosesnya di `sp_process_return` sehingga hanya unit rusak berat yang dipisahkan ke `qty_damaged`, sedangkan unit baik tetap dapat dipinjam selama `qty_available > 0`.

---

## Kasus Debugging 3 — Kebocoran Filter Query pada `scopeSearch` Tanpa Pengelompokan Closure
- **Gejala**:
  Saat memfilter peralatan berdasarkan `status = 'tersedia'` sekaligus kata kunci pencarian, peralatan berstatus `maintenance` ikut muncul di katalog peminjam.
- **Akar Masalah (Root Cause)**:
  Penggunaan `->where('name', 'like', ...)->orWhere('code', 'like', ...)` tanpa kurung pengelompokan menghasilkan SQL `WHERE status = 'tersedia' AND name LIKE ? OR code LIKE ?`, di mana operator `AND` memiliki preseden lebih tinggi daripada `OR`.
- **Solusi Perbaikan**:
  Membungkus seluruh kondisi pencarian di dalam closure `$query->where(function (Builder $q) use ($term) { ... })` pada `User`, `Category`, `Equipment`, `Loan`, `EquipmentReturn`, dan `ActivityLog`.

---

## Kasus Debugging 4 — Kegagalan Build Vite (`UNRESOLVED_IMPORT './bootstrap'`)
- **Gejala Error Saat Kompilasi**:
  Saat menjalankan `npm run build`, Vite v8 gagal pada tahap bundling dengan pesan:
  `[UNRESOLVED_IMPORT] Could not resolve './bootstrap' in resources/js/app.js`.
- **Akar Masalah (Root Cause)**:
  File `resources/js/app.js` awal mengimpor `./bootstrap`, sedangkan struktur skeleton minimal tidak menyertakan file `resources/js/bootstrap.js`.
- **Solusi Perbaikan**:
  Menghapus baris impor yang tidak terpakai pada `resources/js/app.js` dan menginisialisasi `Alpine.js` secara langsung. Hasil `npm run build` kemudian berhasil dikompilasi dalam `338ms` tanpa error.

---

## Kasus Debugging 5 — Selisih Zona Waktu (`UTC` vs `Asia/Jakarta`) pada Perhitungan Hari Keterlambatan
- **Gejala**:
  Antara pukul `00:00` hingga `06:59` WIB, fungsi tanggal PHP `now()->toDateString()` masih menunjukkan tanggal kemarin (`UTC`), sedangkan fungsi `CURDATE()` pada MariaDB mengikuti waktu lokal sistem operasi Windows (`WIB / UTC+7`).
- **Solusi Perbaikan**:
  Menyelaraskan `APP_TIMEZONE=Asia/Jakarta` pada `.env` dan `config/app.php` serta mengatur `Carbon::setLocale('id')` pada `AppServiceProvider`.

---

## Kasus Debugging 6 — Korupsi Tabel Aria `global_priv` pada XAMPP MariaDB (`SQLSTATE[HY000] [1130]`)
- **Gejala**:
  Setelah proses `mysqld.exe` mengalami *unexpected shutdown* (`mysqld.dmp`), koneksi ke `127.0.0.1:3306` maupun `phpMyAdmin` ditolak dengan pesan:
  `ERROR 1130 (HY000): Host 'localhost' is not allowed to connect to this MariaDB server`.
- **Akar Masalah (Root Cause)**:
  Pemeriksaan read-only menggunakan utilitas resmi `aria_chk.exe -c C:\xampp\mysql\data\mysql\global_priv.MAI` menunjukkan `Got error: 176 when reading datafile — Aria table 'C:\xampp\mysql\data\mysql\global_priv.MAI' is corrupted`. Karena tabel sistem `global_priv` rusak akibat *crash*, MariaDB gagal memuat daftar hak akses host saat startup.
- **Solusi Perbaikan Resmi (SOP XAMPP MariaDB)**:
  1. Pastikan servis MySQL di XAMPP dalam keadaan **Stop**.
  2. Jalankan perbaikan indeks Aria resmi dari folder `C:\xampp\mysql\data`:
     ```powershell
     cd C:\xampp\mysql\data
     C:\xampp\mysql\bin\aria_chk.exe -r mysql\*.MAI
     ```
     *(Atau salin ulang file `global_priv.*` dari folder bawaan `C:\xampp\mysql\backup\mysql\` ke `C:\xampp\mysql\data\mysql\` setelah mem-backup folder `data`).*
  3. Klik **Start** kembali pada **MySQL** di XAMPP Control Panel, lalu jalankan `php artisan migrate:fresh --seed`.
