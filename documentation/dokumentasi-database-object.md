# 10. Dokumentasi Database Object (Stored Procedure, Function, Trigger & Transaction)

Seluruh objek basis data didefinisikan secara terukur pada file migrasi `database/migrations/2025_01_01_000009_create_database_objects.php` dan file dump `database/sql/gearhub_db.sql`.

---

## 10.1 Pembagian Tanggung Jawab (Pencegahan Pemotongan Stok Ganda)
Untuk menghindari *double-mutation* antara kode PHP dan objek database:
- **Stored Functions** hanya bertugas menghitung nilai (`READS SQL DATA` / `DETERMINISTIC`).
- **Stored Procedures** memegang otoritas tunggal atas perubahan kuantitas stok saat persetujuan pinjaman (`sp_approve_loan`) dan saat verifikasi pengembalian (`sp_process_return`). Kode PHP **tidak** melakukan pengurangan/penambahan stok ulang di luar prosedur tersebut.
- **Triggers** hanya bertugas menjaga batas validitas stok (`BEFORE INSERT/UPDATE`) dan mencatat *audit log* otomatis (`AFTER UPDATE`), tanpa mengubah angka stok lagi.
- **Transactions** (`DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()`) membungkus pemanggilan prosedur sehingga apabila `SIGNAL SQLSTATE '45000'` dilempar di tengah proses, seluruh perubahan dibatalkan secara utuh.

---

## 10.2 Daftar Lengkap Objek Database

### A. Stored Functions
1. **`fn_count_late_days(p_due_date DATE, p_return_date DATE) RETURNS INT`**
   - **Tujuan**: Menghitung selisih hari keterlambatan antara tanggal pengembalian aktual dan tanggal jatuh tempo. Jika dikembalikan sebelum atau tepat pada `p_due_date`, mengembalikan `0`.
   - **Cara Uji Manual di SQL**:
     ```sql
     SELECT fn_count_late_days('2026-11-10', '2026-11-14') AS hari_terlambat; -- Hasil: 4
     SELECT fn_count_late_days('2026-11-10', '2026-11-09') AS tepat_waktu;    -- Hasil: 0
     ```

2. **`fn_calculate_fine(p_due_date DATE, p_return_date DATE) RETURNS DECIMAL(12,2)`**
   - **Tujuan**: Membaca nilai `fine_rate_per_day` dari tabel `settings` (default `5000.00`) dan mengalikan dengan hasil `fn_count_late_days`.
   - **Cara Uji Manual di SQL**:
     ```sql
     SELECT fn_calculate_fine('2026-11-10', '2026-11-13') AS total_denda; -- Hasil: 15000.00 (jika tarif Rp5.000)
     ```

---

### B. Stored Procedures
1. **`sp_approve_loan(IN p_loan_id BIGINT UNSIGNED, IN p_staff_id BIGINT UNSIGNED)`**
   - **Langkah Eksekusi**:
     1. Mengunci baris `loans` (`FOR UPDATE`) dan memastikan `status = 'pending'`.
     2. Membuka *cursor* atas seluruh item di `loan_details`.
     3. Mengunci baris `equipments` (`FOR UPDATE`), memastikan `status = 'tersedia'` dan `qty_available >= qty`. Bila tidak terpenuhi, melempar `SIGNAL SQLSTATE '45000'` untuk memicu `ROLLBACK`.
     4. Mengurangi `qty_available` dan menyesuaikan `equipments.status` apabila stok habis.
     5. Mengubah `loans.status` menjadi `'approved'`, mengisi `approved_by` dan `approved_at = NOW()`.

2. **`sp_process_return(IN p_return_id BIGINT UNSIGNED, IN p_staff_id BIGINT UNSIGNED, IN p_fine_paid TINYINT(1), IN p_notes TEXT)`**
   - **Langkah Eksekusi**:
     1. Mengunci baris `returns` dan `loans` (`FOR UPDATE`) serta memastikan detail pemeriksaan fisik pada `return_details` telah terisi.
     2. Menyalin tarif aktif `fine_rate_per_day` ke `fine_per_day_snapshot` serta menghitung `late_days` dan `fine_amount` menggunakan `fn_count_late_days` dan `fn_calculate_fine`.
     3. Untuk setiap baris `return_details`:
        - Menghitung unit layak pakai `v_good_qty = qty_returned - qty_damaged`.
        - Menambahkan `v_good_qty` ke `equipments.qty_available`.
        - Menambahkan `qty_damaged` ke `equipments.qty_damaged` (**unit rusak berat tidak masuk ke stok tersedia**).
        - Memperbarui kondisi dan status ketersediaan peralatan.
     4. Menandai `returns.status = 'verified'` dan `loans.status = 'returned'`.

---

### C. Triggers
1. **`trg_equipments_stock_guard_insert` (`BEFORE INSERT ON equipments`) & `trg_equipments_stock_guard_update` (`BEFORE UPDATE ON equipments`)**
   - Menolak perintah `INSERT` atau `UPDATE` dengan `SIGNAL SQLSTATE '45000'` apabila `qty_available > qty_total` atau `(qty_available + qty_damaged) > qty_total`.
2. **`trg_loans_status_audit` (`AFTER UPDATE ON loans`)**
   - Setiap kali kolom `loans.status` berubah nilai (`OLD.status <> NEW.status`), otomatis menyisipkan baris jejak audit ke `activity_logs` dengan `module = 'db_trigger'` beserta objek JSON nilai lama dan baru.
3. **`trg_settings_value_audit` (`AFTER UPDATE ON settings`)**
   - Setiap kali nilai konfigurasi sistem (seperti tarif denda per hari) diperbarui, otomatis mencatat nilai sebelum dan sesudah perubahan ke `activity_logs`.
