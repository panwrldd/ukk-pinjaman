# 9. Dokumentasi Fungsi, Prosedur, dan Method per Modul

Sesuai ketentuan Langkah Kerja poin 4 pada dokumen UKK, berikut spesifikasi **Input, Proses, dan Output** dari method layanan utama di setiap modul aplikasi.

---

## 9.1 Modul Autentikasi (`App\Http\Controllers\AuthController`)

| Method | Input | Proses | Output |
| :--- | :--- | :--- | :--- |
| `showLogin()` | HTTP `GET /login` | Memeriksa `Auth::check()`; jika sudah login diarahkan ke dashboard role, jika belum menampilkan view `auth.login`. | `View` atau `RedirectResponse` |
| `login(LoginRequest $request)` | `identity` (string), `password` (string), `remember` (bool) | Memeriksa rate limiter, mencocokkan kolom `email` atau `username`, memverifikasi hash `Bcrypt`, memeriksa `is_active`, membuat sesi, dan mencatat log `login`. | `RedirectResponse` ke dashboard sesuai role |
| `logout(Request $request)` | HTTP `POST /logout` + CSRF | Mencatat log `logout`, memanggil `Auth::logout()`, menginvalidasi sesi, dan meregenerasi CSRF token. | `RedirectResponse` ke `/login` |

---

## 9.2 Modul Inventaris Peralatan (`App\Services\EquipmentService`)

| Method | Input | Proses | Output |
| :--- | :--- | :--- | :--- |
| `createEquipment($data, $photo, $actorId)` | Data atribut alat, file foto opsional, ID admin | Membuka transaksi, memvalidasi `qty_available + qty_damaged <= qty_total`, menyimpan foto ke `storage/app/public/equipments`, menyimpan baris `equipments`, mencatat `activity_logs`, lalu `COMMIT`. | Objek model `Equipment` |
| `updateEquipment($equipment, $data, $photo, $removePhoto, $actorId)` | Model `Equipment`, data baru, foto baru, flag hapus foto, ID admin | Mengunci baris `FOR UPDATE`, menghitung `qty_borrowed` aktif, memastikan `qty_total` baru tidak di bawah `qty_borrowed + qty_damaged`, menyesuaikan `qty_available` secara proporsional, mengelola file foto, lalu `COMMIT`. | Objek `Equipment` terbaru |
| `restoreDamagedUnits($equipment, $qtyToRestore, $actorId, $notes)` | Model `Equipment`, jumlah unit selesai diperbaiki, ID admin, catatan | Mengurangi `qty_damaged`, menambahkan jumlah tersebut ke `qty_available`, mengubah status menjadi `tersedia`, dan mencatat log pemulihan servis. | Objek `Equipment` yang telah pulih |
| `deleteEquipment($equipment, $actorId)` | Model `Equipment`, ID admin | Menolak penghapusan apabila alat sudah tercatat di `loan_details` (menjaga integritas histori), menghapus foto dari storage, menghapus record, lalu `COMMIT`. | `void` |

---

## 9.3 Modul Peminjaman (`App\Services\LoanService`)

| Method | Input | Proses | Output |
| :--- | :--- | :--- | :--- |
| `createLoan(array $data, int $userId)` | `loan_date`, `due_date`, `purpose`, `items[]`, ID peminjam | Validasi durasi & kuota pinjaman aktif, cek duplikasi pengajuan `pending`, kunci baris `equipments` (`FOR UPDATE`), simpan header `loans` (`pending`) dan `loan_details`, lalu `COMMIT` (`ROLLBACK` bila gagal). | Objek `Loan` beserta relasi |
| `approveLoan(int $loanId, int $staffId, ?string $notes)` | ID pinjaman, ID petugas, catatan opsional | Membuka transaksi, memanggil `CALL sp_approve_loan(?, ?)` untuk memotong stok `qty_available` dan mengubah status ke `approved`, mencatat log, lalu `COMMIT`. | Objek `Loan` berstatus `approved` |
| `rejectLoan(int $loanId, int $staffId, string $reason)` | ID pinjaman, ID petugas, alasan penolakan | Memvalidasi status `pending` & alasan wajib, memperbarui status ke `rejected` tanpa memotong stok, lalu `COMMIT`. | Objek `Loan` berstatus `rejected` |
| `handOverLoan(int $loanId, int $staffId, ?string $notes)` | ID pinjaman, ID petugas, catatan | Mengubah status `approved -> borrowed` serta mengisi `handed_over_by` dan `handed_over_at`. | Objek `Loan` berstatus `borrowed` |
| `cancelLoan(int $loanId, int $actorId, bool $isStaffOrAdmin)` | ID pinjaman, ID pelaku, flag petugas/admin | Membatalkan pengajuan `pending` (untuk peminjam) atau membatalkan pinjaman `approved` yang batal diambil sambil mengembalikan `qty_available` secara atomik (untuk petugas/admin). | Objek `Loan` berstatus `cancelled` |
| `syncOverdueStatuses()` | Tanggal sistem (`Carbon::today()`) | Memperbarui seluruh baris `loans` berstatus `borrowed` yang `due_date < hari ini` menjadi `overdue`. | Jumlah baris yang diperbarui (`int`) |

---

## 9.4 Modul Pengembalian & Denda (`App\Services\ReturnService`)

| Method | Input | Proses | Output |
| :--- | :--- | :--- | :--- |
| `previewFine(string $dueDate, ?string $returnDate)` | Tanggal jatuh tempo, tanggal kembali | Mengeksekusi `SELECT fn_count_late_days(?, ?), fn_calculate_fine(?, ?)` pada MariaDB. | Array `['late_days' => int, 'fine_amount' => float]` |
| `submitReturn(int $loanId, int $actorId, ?string $notes, ?string $returnDate)` | ID pinjaman aktif, ID peminjam, catatan | Memastikan status pinjaman `borrowed`/`overdue` dan belum punya pengembalian (`UNIQUE loan_id`), mengambil estimasi denda awal, menyimpan baris `returns` (`pending_verification`). | Objek `EquipmentReturn` |
| `verifyReturn(int $returnId, int $staffId, array $details, bool $finePaid, ?string $notes)` | ID pengembalian, ID petugas, array pemeriksaan tiap item, status bayar denda | Membuka transaksi, menyimpan rincian inspeksi fisik ke `return_details`, mengeksekusi `CALL sp_process_return(?, ?, ?, ?)`, mencatat log verifikasi, lalu `COMMIT` (`ROLLBACK` bila ada error). | Objek `EquipmentReturn` berstatus `verified` |

---

## 9.5 Modul Pelaporan (`App\Services\ReportService` & `App\Http\Controllers\ReportController`)

| Method | Input | Proses | Output |
| :--- | :--- | :--- | :--- |
| `ReportService::buildReport($type, $filters)` | Jenis laporan (`loans`, `returns`, `overdue`, `fines`, `equipments`, `categories`, `users`), parameter filter | Sinkronisasi status `overdue`, query relasional dengan `with(...)`, perhitungan kartu ringkasan, penyusunan header dan baris matriks laporan. | Array terstruktur (`meta`, `records`, `summary`, `headings`, `rows`) |
| `ReportController::exportPdf($request)` | Tipe laporan & filter aktif | Memeriksa otorisasi laporan per role, merender view `reports.pdf` melalui `DomPDF` ukuran A4, dan mencatat log ekspor. | File unduhan `.pdf` |
| `ReportController::exportExcel($request)` | Tipe laporan & filter aktif | Membungkus matriks data ke `TabularReportExport` (`Maatwebsite Excel`) dan menghasilkan spreadsheet otomatis rapi. | File unduhan `.xlsx` |
