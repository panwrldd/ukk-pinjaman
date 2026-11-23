# 8. Flowchart & Pseudocode — Pengembalian Alat & Perhitungan Denda

## 8.1 Diagram Alur (Flowchart) Pengembalian & Denda

```mermaid
flowchart TD
    Start(["Mulai"]) --> Ajukan["Peminjam Mengajukan Pengembalian pada Pinjaman Aktif"]
    Ajukan --> SimpanRet["Insert returns (status: pending_verification, return_date: CURDATE)"]
    SimpanRet --> SerahLoket["Peminjam Menyerahkan Fisik Alat ke Loket Petugas"]
    SerahLoket --> Inspeksi["Petugas Memeriksa Kondisi Fisik Setiap Alat (Baik / Rusak Ringan / Rusak Berat)"]
    Inspeksi --> MulaiTx["BEGIN TRANSACTION (ReturnService::verifyReturn)"]
    MulaiTx --> SimpanDetail["Simpan return_details (qty_returned, qty_damaged, condition_after, damage_notes)"]
    SimpanDetail --> CallSP["CALL sp_process_return(return_id, staff_id, fine_paid, notes)"]
    CallSP --> HitungTelat["v_late_days = fn_count_late_days(due_date, return_date)"]
    HitungTelat --> CekTelat{"v_late_days > 0?"}
    CekTelat -- "Tidak" --> DendaNol["v_fine_amount = Rp0"]
    CekTelat -- "Ya" --> HitungDenda["v_fine_amount = fn_calculate_fine(due_date, return_date) = v_late_days x fine_rate_per_day"]
    DendaNol --> LoopItem["Iterasi Setiap Item pada return_details"]
    HitungDenda --> LoopItem
    LoopItem --> CekKondisi{"Ada Unit Rusak Berat (qty_damaged > 0)?"}
    CekKondisi -- "Ya" --> PisahRusak["Unit Rusak -> Tambah ke equipments.qty_damaged (TIDAK Masuk qty_available)"]
    CekKondisi -- "Tidak" --> PulihStok["Unit Layak Pakai -> Tambah ke equipments.qty_available"]
    PisahRusak --> SisaBaik["Unit Baik Sisanya (jika ada) -> Tambah ke qty_available"]
    SisaBaik --> UpdateStatusAlat["Sesuaikan equipments.status (tersedia / maintenance / tidak_tersedia)"]
    PulihStok --> UpdateStatusAlat
    UpdateStatusAlat --> UpdateHeader["Update returns (status: verified, fine_amount) & loans (status: returned)"]
    UpdateHeader --> CommitTx["Trigger Audit Berjalan -> COMMIT Transaksi"]
    CommitTx --> End(["Selesai — Pengembalian Terverifikasi"])
```

---

## 8.2 Pseudocode Perhitungan Denda & Pembaruan Stok (`sp_process_return`)

```text
FUNCTION fn_calculate_fine(due_date, return_date) RETURNS DECIMAL(12,2)
BEGIN
    late_days <- MAX(0, DATEDIFF(return_date, due_date))
    IF late_days == 0 THEN
        RETURN 0.00
    END IF

    fine_rate <- SELECT CAST(value AS DECIMAL) FROM settings WHERE key = 'fine_rate_per_day'
    IF fine_rate IS NULL THEN
        fine_rate <- 5000.00
    END IF

    RETURN late_days * fine_rate
END

PROCEDURE sp_process_return(return_id, staff_id, fine_paid, notes)
BEGIN
    ret  <- SELECT * FROM returns WHERE id = return_id FOR UPDATE
    loan <- SELECT * FROM loans WHERE id = ret.loan_id FOR UPDATE

    late_days   <- fn_count_late_days(loan.due_date, ret.return_date)
    fine_amount <- fn_calculate_fine(loan.due_date, ret.return_date)

    FOR EACH detail IN (SELECT * FROM return_details WHERE return_id = return_id) DO
        good_qty <- detail.qty_returned - detail.qty_damaged
        eq <- SELECT * FROM equipments WHERE id = detail.equipment_id FOR UPDATE

        new_avail   <- eq.qty_available + good_qty
        new_damaged <- eq.qty_damaged + detail.qty_damaged
        -- Catatan Penting: Unit rusak berat TIDAK dikembalikan ke qty_available

        UPDATE equipments
        SET qty_available = new_avail,
            qty_damaged   = new_damaged,
            status        = IF(new_avail > 0, 'tersedia', IF(new_damaged > 0, 'maintenance', 'tidak_tersedia'))
        WHERE id = eq.id
    END FOR

    UPDATE returns SET status = 'verified', verified_by = staff_id, late_days = late_days, fine_amount = fine_amount
    UPDATE loans   SET status = 'returned'
END
```
