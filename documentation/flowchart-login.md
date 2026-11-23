# 6. Flowchart & Pseudocode — Proses Login

## 6.1 Diagram Alur (Flowchart) Login Berdasarkan Role

```mermaid
flowchart TD
    Start(["Mulai"]) --> BukaLogin["Pengguna Membuka Halaman /login"]
    BukaLogin --> CekSesi{"Sudah Memiliki Sesi Aktif?"}
    CekSesi -- "Ya" --> RedirectRole["Redirect Otomatis Sesuai Role"]
    CekSesi -- "Tidak" --> InputForm["Input Identity (Username / Email) & Password"]
    InputForm --> ValidasiInput{"Input Terisi Valid?"}
    ValidasiInput -- "Tidak" --> PesanValidasi["Tampilkan Pesan Validasi Form"] --> InputForm
    ValidasiInput -- "Ya" --> CekLimit{"Melebihi 5x Gagal / Menit?"}
    CekLimit -- "Ya" --> BlokirSementara["Tampilkan Peringatan Rate Limit"] --> InputForm
    CekLimit -- "Tidak" --> CariUser["Cari User berdasarkan Email atau Username"]
    CariUser --> CekHash{"User Ditemukan & Hash Password Cocok?"}
    CekHash -- "Tidak" --> HitLimit["Tambah Counter Gagal & Tampilkan Pesan Kredensial Salah"] --> InputForm
    CekHash -- "Ya" --> CekAktif{"Akun Berstatus is_active = 1?"}
    CekAktif -- "Tidak" --> TolakNonaktif["Tolak Login & Tampilkan Pesan Akun Dinonaktifkan"] --> InputForm
    CekAktif -- "Ya" --> BuatSesi["Auth::login, Regenerasi Session ID & Catat activity_logs"]
    BuatSesi --> RedirectRole
    RedirectRole --> CekRole{"Periksa Nilai user.role"}
    CekRole -- "admin" --> DashAdmin["/admin/dashboard"]
    CekRole -- "petugas" --> DashStaff["/staff/dashboard"]
    CekRole -- "peminjam" --> DashBorrower["/borrower/dashboard"]
    DashAdmin --> End(["Selesai"])
    DashStaff --> End
    DashBorrower --> End
```

---

## 6.2 Pseudocode Proses Autentikasi (`AuthController@login`)

```text
PROCEDURE Login(identity, password, remember, client_ip)
BEGIN
    IF identity IS EMPTY OR password IS EMPTY THEN
        RETURN ValidationError("Username/email dan kata sandi wajib diisi")
    END IF

    throttle_key <- LOWER(identity) + "|" + client_ip
    IF RateLimiter.tooManyAttempts(throttle_key, 5) THEN
        RETURN Error("Terlalu banyak percobaan login gagal")
    END IF

    IF IsEmailFormat(identity) THEN
        user <- SELECT * FROM users WHERE email = identity LIMIT 1
    ELSE
        user <- SELECT * FROM users WHERE username = identity LIMIT 1
    END IF

    IF user IS NULL OR NOT BcryptVerify(password, user.password) THEN
        RateLimiter.hit(throttle_key, 60)
        RETURN Error("Username/email atau kata sandi salah")
    END IF

    IF user.is_active == FALSE THEN
        RETURN Error("Akun sedang dinonaktifkan")
    END IF

    RateLimiter.clear(throttle_key)
    Session.authenticate(user, remember)
    Session.regenerateId()
    ActivityLogService.log("login", "Pengguna berhasil login", "auth", user.id)

    SWITCH user.role
        CASE "admin":    REDIRECT "/admin/dashboard"
        CASE "petugas":  REDIRECT "/staff/dashboard"
        CASE "peminjam": REDIRECT "/borrower/dashboard"
    END SWITCH
END
```
