# 5. Dokumentasi Entity Relationship Diagram (ERD)

## 5.1 Diagram Relasi Entitas (`gearhub_db`)

```mermaid
erDiagram
    USERS ||--o{ LOANS : "mengajukan (user_id)"
    USERS ||--o{ LOANS : "memverifikasi (approved_by)"
    USERS ||--o{ LOANS : "menyerahkan (handed_over_by)"
    USERS ||--o{ RETURNS : "mengembalikan (returned_by)"
    USERS ||--o{ RETURNS : "memeriksa (verified_by)"
    USERS ||--o{ ACTIVITY_LOGS : "pelaku (user_id)"
    USERS ||--o{ SETTINGS : "mengubah (updated_by)"

    CATEGORIES ||--o{ EQUIPMENTS : "mengelompokkan (category_id)"
    LOANS ||--|{ LOAN_DETAILS : "memiliki_rincian (loan_id)"
    EQUIPMENTS ||--o{ LOAN_DETAILS : "dipinjam_pada (equipment_id)"

    LOANS ||--o| RETURNS : "diselesaikan_oleh (loan_id)"
    RETURNS ||--|{ RETURN_DETAILS : "memiliki_inspeksi (return_id)"
    LOAN_DETAILS ||--o| RETURN_DETAILS : "diverifikasi_ke (loan_detail_id)"
    EQUIPMENTS ||--o{ RETURN_DETAILS : "diperiksa_fisik (equipment_id)"

    USERS {
        bigint id PK
        varchar username UK
        varchar email UK
        varchar identity_number UK
        varchar name
        enum role
        boolean is_active
    }

    CATEGORIES {
        bigint id PK
        varchar code UK
        varchar name
        varchar slug UK
        boolean is_active
    }

    EQUIPMENTS {
        bigint id PK
        bigint category_id FK
        varchar code UK
        varchar name
        enum condition
        uint qty_total
        uint qty_available
        uint qty_damaged
        enum status
    }

    LOANS {
        bigint id PK
        varchar code UK
        bigint user_id FK
        date loan_date
        date due_date
        enum status
        bigint approved_by FK
        bigint handed_over_by FK
    }

    LOAN_DETAILS {
        bigint id PK
        bigint loan_id FK
        bigint equipment_id FK
        uint qty
        enum condition_before
    }

    RETURNS {
        bigint id PK
        varchar code UK
        bigint loan_id FK_UK
        bigint returned_by FK
        bigint verified_by FK
        date return_date
        uint late_days
        decimal fine_amount
        boolean fine_paid
        enum status
    }

    RETURN_DETAILS {
        bigint id PK
        bigint return_id FK
        bigint loan_detail_id FK_UK
        bigint equipment_id FK
        uint qty_returned
        uint qty_damaged
        enum condition_after
    }

    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        varchar module
        varchar action
        json old_values
        json new_values
    }

    SETTINGS {
        bigint id PK
        varchar key UK
        text value
        bigint updated_by FK
    }
```

---

## 5.2 Penjelasan Kardinalitas Antar Tabel

1. **One-to-Many (`1 : N`)**:
   - `categories (1) -> equipments (N)`: Satu kategori menaungi banyak jenis peralatan; satu peralatan wajib memiliki tepat satu kategori (`ON DELETE RESTRICT`).
   - `users (1) -> loans (N)`: Satu pengguna peminjam dapat memiliki banyak riwayat pengajuan peminjaman (`ON DELETE RESTRICT`).
   - `loans (1) -> loan_details (N)`: Satu transaksi peminjaman dapat memuat satu atau lebih baris peralatan berbeda (`ON DELETE CASCADE`).
   - `returns (1) -> return_details (N)`: Satu transaksi pengembalian memuat rincian hasil pemeriksaan untuk setiap item yang dipinjam (`ON DELETE CASCADE`).
   - `users (1) -> activity_logs (N)`: Satu pengguna dapat menghasilkan banyak catatan log aktivitas (`ON DELETE SET NULL` agar log historis tetap utuh walau akun dihapus).

2. **One-to-One (`1 : 1`)**:
   - `loans (1) -> returns (1)`: Ditegakkan melalui constraint `UNIQUE (loan_id)` pada tabel `returns`. Satu transaksi peminjaman hanya dapat memiliki tepat satu transaksi penyelesaian pengembalian.
   - `loan_details (1) -> return_details (1)`: Ditegakkan melalui constraint `UNIQUE (loan_detail_id)` pada tabel `return_details`. Setiap baris alat yang dipinjam memiliki tepat satu baris hasil verifikasi kondisi pengembalian.

3. **Resolusi Many-to-Many (`M : N`)**:
   - Hubungan banyak-ke-banyak antara transaksi `loans` dan inventaris `equipments` dipecah secara ternormalisasi melalui tabel asosiatif `loan_details` dengan constraint gabungan `UNIQUE (loan_id, equipment_id)`.
