# Dokumentasi Aplikasi — Simpels

> Sistem Informasi Peminjaman Laptop Sekolah (SMK).
> Dokumen ini menjelaskan alur kerja aplikasi secara lengkap, seluruh komponen yang dipakai, library, dan arsitekturnya.

---

## 1. Gambaran Umum

**Simpels** adalah aplikasi web untuk mengelola peminjaman laptop di lingkungan sekolah. Ada dua jenis pengguna:

| Peran | Kemampuan |
|---|---|
| **Siswa** (member) | Login, scan QR laptop untuk meminjam (maks 5 siswa/laptop), melihat bukti peminjaman, mengajukan pengembalian. |
| **Admin / Super Admin** | Login via pintu khusus, verifikasi pengembalian, kelola data laptop + QR, kelola akun siswa & admin, kelola master Kelas & Jurusan, lihat dashboard statistik. |

Alur inti aplikasi adalah siklus: **Laptop Tersedia → Dipinjam (siswa) → Dikembalikan (diajukan siswa) → Diverifikasi Admin → Tersedia lagi.**

Catatan: aplikasi **tidak memiliki registrasi mandiri** — akun dibuat/dikelola oleh Admin. Tidak memakai Vite/npm build; semua aset statis di `public/assets`.

---

## 2. Tumpukan Teknologi

| Komponen | Versi / Detail |
|---|---|
| PHP | ^8.3 (runtime 8.3.30) |
| Framework | Laravel 13.32.0 |
| Database | MySQL (`pemlaptex`) untuk runtime; SQLite `:memory:` untuk test |
| Template | Blade (server-side), teks statis (`asset()`), tanpa build step |
| CSS/UI | Bootstrap 5, Bootstrap Icons, custom CSS di `public/assets/css` |
| Scanner QR | `html5-qrcode` (klien, kamera via browser) |
| Generator QR | `simplesoftwareio/simple-qrcode` (server, PNG) |
| Session / Cache | driver `database` (tabel `sessions`, `cache`) |
| Auth guard | `web` |
| Tools dev | Pint (formatter), PHPUnit 12, Mockery, Faker, Laravel Boost/Pail/Pao |

---

## 3. Struktur Folder

```
app/
  Http/Controllers/        # Semua logic HTTP (9 controller)
    AdminController.php        # Kelola laptop, user, verifikasi, QR
    AuthController.php         # Login/logout (pintu siswa & admin)
    BorrowingController.php    # Alur peminjaman: scan → konfirmasi → store → bukti
    DashboardController.php    # Dashboard admin & siswa (invokable)
    ReturnController.php       # Alur pengembalian dari sisi siswa
    KelasController.php        # CRUD master Kelas
    JurusanController.php      # CRUD master Jurusan
  Http/Middleware/
    EnsureUserIsAdmin.php      # alias "admin"
    EnsureUserIsSiswa.php      # alias "siswa"
  Models/                  # Borrowing, BorrowingStudent, Jurusan, Kelas,
                           # Laptop, ReturnRequest, User
  Notifications/
    ReturnRequestNotification.php
bootstrap/app.php          # Konfigurasi alias middleware
database/
  migrations/              # 13 file migrasi (skema dibuat di §4)
  factories/               # User, Laptop, Borrowing, Kelas, Jurusan
  seeders/                 # ReferenceSeeder (kelas/jurusan), DatabaseSeeder (user+laptop)
public/assets/
  css/  (main.css, siswa.css, login-*.css)
  js/   (auth.js, dashboard.js)
  images/ (logo-simpel.png, avatar.png)
  libs/ (bootstrap, bootstrap-icons, html5-qrcode)   # library statis
  storage/qrcodes/         # file gambar QR hasil generate (simlink storage:link)
resources/views/
  layouts/                 # app.blade.php (layout admin), app-siswa.blade.php, partials/
  auth/                    # login.blade.php, login-admin.blade.php
  admin/                   # laptop*, user*, verifikasi, kelas, jurusan, qr-print
  pinjam/                  # scan, konfirmasi, bukti
  kembali/index.blade.php
  dashboard/               # admin.blade.php, siswa.blade.php
routes/web.php             # Semua route web
tests/Feature/             # 8 file test (PHPUnit)
```

---

## 4. Basis Data

### 4.1 Tabel & Relasi

```
users ──┬── kelas_id ──► kelas
        └── jurusan_id ─► jurusan

borrowings ──┬── laptop_id ──► laptops
             └── created_by ──► users (leader/ketua sesi)

borrowing_students (pivot users ⇄ borrowings, kolom: borrowing_id, user_id, timestamps)

return_requests ── borrowing_id ──► borrowings

laptops (qr_token unik, status: tersedia/dipinjam)
notifications (tabel notifikasi database driver)
sessions, cache, cache_locks, jobs*, failed_jobs, migrations, password_reset_tokens
```

### 4.2 Kolom kunci tiap tabel (ringkas)

| Tabel | Kolom penting |
|---|---|
| `users` | `username` (log-in), `password` (hashed), `role` (`siswa`/`admin`/`superadmin`), `kelas_id`, `jurusan_id` |
| `kelas` | `nama` (mis. `X`, `XI`, `XII`) |
| `jurusan` | `kode` + `nama` (mis. `RPL`, `TKJ`, …) |
| `laptops` | `nama`, `merek`, `spesifikasi`, `qr_token` (UUID), `status` |
| `borrowings` | `laptop_id`, `created_by` (leader), `borrowed_at`, `returned_at`, `condition`, `status` |
| `borrowing_students` | pivot banyak-ke-banyak user ⇄ borrowing (+ timestamps) |
| `return_requests` | `borrowing_id`, `status`, `condition`, `admin_notes`, `requested_at`, `responded_at` |

### 4.3 Status / State Machine

**`borrowings.status`**
- `aktif` — sedang dipinjam.
- `menunggu` — pengembalian diajukan, menunggu verifikasi admin.
- `dikembalikan` — selesai, laptop kembali tersedia.

**`return_requests.status`**
- `menunggu` → `disetujui` / `ditolak`

**`laptops.status`**
- `tersedia` / `dipinjam` (diubah sinkron dengan transaksi peminjaman/pengembalian)

---

## 5. Autentikasi & Middleware

### 5.1 Dua pintu login
- **Siswa**: `GET/POST /login` + middleware `guest` + throttle `login`.
- **Admin**: `GET/POST /admin` + middleware `guest` + throttle `login`.

Admin yang login di pintu siswa **tidak bisa** — `AuthController::login` mencocokkan role (`matchesRole`): pintu siswa hanya menerima `siswa`; pintu admin menerima `admin`/`superadmin`. Ada **dummy hash** untuk membuat waktu brute-force seragam saat username tidak ditemukan (`AuthController.php:14`).

**Logout** mengarahkan kembali ke pintu sesuai peran (siswa/`login`, admin/`login.admin`).

### 5.2 Middleware kustom
| Alias | Class | Arahkan |
|---|---|---|
| `admin` | `EnsureUserIsAdmin` | `403` bila bukan admin/superadmin |
| `siswa` | `EnsureUserIsSiswa` | `403` bila bukan siswa |

Daftar di `bootstrap/app.php:17-20`. Bundle bawaan Laravel yang terpakai: `auth`, `guest`, `throttle:login`.

---

## 6. Routing Lengkap

| Method | URI | Nama route | Controller@method | Middleware |
|---|---|---|---|---|
| GET | `/` | — | redirect ke dashboard/login | — |
| GET | `/login` | `login` | `AuthController@showLogin` | guest |
| POST | `/login` | `login` | `AuthController@login` (role=siswa) | guest, throttle:login |
| GET | `/admin` | `login.admin` | `AuthController@showAdminLogin` | guest |
| POST | `/admin` | `login.admin` | `AuthController@login` (role=admin) | guest, throttle:login |
| POST | `/logout` | `logout` | `AuthController@logout` | auth |
| GET | `/dashboard` | `dashboard` | `DashboardController` (invokable) | auth |
| POST | `/notifications/read` | `notifications.read` | `DashboardController@markNotificationsRead` | auth |
| GET | `/pinjam` | `pinjam.scan` | `BorrowingController@scan` | auth, siswa |
| POST | `/pinjam/validasi` | `pinjam.validasi` | `BorrowingController@validasi` | auth, siswa |
| GET | `/pinjam/{laptop}` | `pinjam.konfirmasi` | `BorrowingController@konfirmasi` | auth, siswa |
| POST | `/pinjam/{laptop}/konfirmasi` | `pinjam.store` | `BorrowingController@store` | auth, siswa |
| GET | `/bukti/{borrowing}` | `pinjam.bukti` | `BorrowingController@bukti` | auth, siswa |
| GET | `/kembali` | `kembali.index` | `ReturnController@index` | auth, siswa |
| POST | `/kembali/{borrowing}` | `kembali.request` | `ReturnController@request` | auth, siswa |
| GET | `/admin/panel` | `admin.dashboard` | redirect ke dashboard | auth, admin |
| GET | `/admin/verifikasi` | `admin.verifikasi.index` | `AdminController@verifikasiIndex` | auth, admin |
| POST | `/admin/verifikasi/{returnRequest}` | `admin.verifikasi` | `AdminController@verifikasi` | auth, admin |
| GET/POST/PUT/DELETE | `/admin/laptop...` | `admin.laptop.*` | `AdminController@laptop*` | auth, admin |
| GET | `/admin/laptop/print` | `admin.laptop.print` | `laptopPrint` | auth, admin |
| GET | `/admin/laptop/{laptop}/qr` | `admin.laptop.qr` | `laptopQr` | auth, admin |
| CRUD | `/admin/user...` | `admin.user.*` (scope=siswa) | `AdminController@user*` | auth, admin |
| CRUD | `/admin/admin-user...` | `admin.adminuser.*` (scope=admin) | `AdminController@user*` | auth, admin |
| CRUD | `/admin/kelas...` | `admin.kelas.*` | `KelasController@*` | auth, admin |
| CRUD | `/admin/jurusan...` | `admin.jurusan.*` | `JurusanController@*` | auth, admin |

Model binding otomatis (route model binding) dipakai untuk `{laptop}`, `{borrowing}`, `{returnRequest}`, `{user}`, `{kelas}`, `{jurusan}`.

---

## 7. Alur Fitur Lengkap

### 7.1 Login

1. Pengguna membuka `/login` (siswa) atau `/admin` (admin).
2. Form mengirim `username` + `password`.
3. `AuthController::login` memvalidasi, mengambil user by `username`, mengecek kecocokan role, lalu `Auth::attempt` (guard `web`).
4. Sukses → `session()->regenerate()` → redirect ke `/dashboard`.
5. Gagal → kembali dengan error "Login Gagal…". Terlampaui batas → throttle `login`.

### 7.2 Dashboard

`DashboardController::__invoke` bercabang sesuai role:
- **Admin** (`dashboard/admin.blade.php`): total laptop, tersedia, dipinjam, jumlah antrian verifikasi, 5 request menunggu terbaru, 8 peminjaman terakhir.
- **Siswa** (`dashboard/siswa.blade.php`): daftar sesi peminjamannya sendiri — dibagi "aktif/menunggu" (`sessionsAktif`) dan selesai (`sessionsSelesai`).

### 7.3 Peminjaman Laptop (scan QR)

1. **`pinjam.scan`** — Halaman berisi bingkai kamera (`#qr-reader`). JS memuat `html5-qrcode.min.js` (di `public/assets/libs/html5-qrcode`), lalu tombol "Mulai Scan" memanggil `Html5Qrcode.start({ facingMode: 'environment' })` — kamera belakang via `getUserMedia`. Setiap decode sukses → mengisi hidden `qr_token` dan submit ke `pinjam.validasi`. (Catatan: kamera butuh koneksi aman — HTTPS atau `localhost`.)
2. **`pinjam.validasi`** (`BorrowingController@validasi`) — Ambil `qr_token`, cari laptop. Jika token kosong / laptop tidak terdaftar / laptop sedang dipinjam → flash `error` dan kembali ke scan. Sukses → redirect ke `pinjam.konfirmasi`.
3. **`pinjam.konfirmasi`** — Menampilkan detail laptop + daftar siswa lain yang **tersedia** (sedang tidak terlibat sesi aktif). Maks 5 siswa per sesi. Filter kelas/jurusan disediakan.
4. **`pinjam.store`** — Validasi `member_ids` (array 1–5, integer distinct, harus role `siswa`). Leader (yang login) otomatis disertakan. Semua operasi di dalam **transaksi DB** + `lockForUpdate` pada baris laptop untuk mencegah race. Cek ulang: laptop tidak dipinjam sesi lain & tidak ada siswa yang sudah terdaftar sesi aktif. Sukses → buat `Borrowing` (status `aktif`), attach siswa ke pivot `borrowing_students`, set laptop `dipinjam`.
5. **`pinjam.bukti`** — Tampilkan struk/receipt (kode `PML/PJM/00001`, data laptop, daftar anggota). Proteksi: `abort_unless(isMember, 403)`. Dapat dicetak (`window.print()`, CSS print khusus). **Catatan Admin** untuk hasil verifikasi tampil di bagian bawah bukti (lihat §7.4).

### 7.4 Pengembalian & Verifikasi

1. **`kembali.index`** — Daftar peminjaman milik user (dengan relasi `returnRequests`). Untuk sesi `aktif` tersedia tombol "Kembalikan Laptop"; bila permintaan terakhir **ditolak**, catatan admin (`admin_notes`) ditampilkan inline.
2. **`kembali.request`** (`ReturnController@request`) — Hanya anggota sesi (`403`) & sesi berstatus `aktif` (`422`). Buat `ReturnRequest` status `menunggu`, ubah `borrowings.status` jadi `menunggu`, lalu **kirim notifikasi database** `ReturnRequestNotification` ke semua admin/superadmin (url → halaman verifikasi).
3. **`admin.verifikasi.index`** — Daftar request `menunggu` (10/halaman), dengam info borrowing.laptop dan student.
4. **`admin.verifikasi`** — Form berisi `keputusan` (`disetujui`/`ditolak`), `condition` (opsional), `admin_notes` (opsional, maks 1000). Request yang sudah diproses ditolak.
   - **Disetujui**: transaksi → request `disetujui` + `responded_at`; borrowing `dikembalikan` + `returned_at` + `condition`; laptop `tersedia`. Notifikasi ke semua siswa anggota (title "Pengembalian Berhasil").
   - **Ditolak**: request `ditolak` + `admin_notes` + `responded_at`; borrowing kembali `aktif`. Notifikasi ke siswa dengan pesan menyertakan catatan.
5. **Catatan Admin** sekarang tampil ke siswa di:
   - halaman `kembali/index.blade.php` (saat keputusan terakhir ditolak);
   - **halaman bukti** `pinjam/bukti.blade.php` (blok "Catatan Admin" di paling bawah, untuk keputusan disetujui **dan** ditolak) — ditambahkan lewat fix terbaru (termasuk eager load `returnRequests` di `BorrowingController@bukti`).

### 7.5 Manajemen Admin

**Laptop** (`admin/laptop*.blade.php`)
- Index: pencarian nama/merek, paginasi 10, menampilkan status + siapa yang sedang meminjam (`activeBorrowings.students`).
- Create/Edit: `nama`, `merek`, `spesifikasi`. Saat create dibuat `qr_token` (UUID). `generateQrImage()` menulis PNG QR (512px, margin 1) ke `storage/app/public/qrcodes/{token}.png`.
- Delete: diblokir saat laptop sedang dipinjam; file QR ikut dihapus.
- Print/Qr: halaman `qr-print.blade.php` untuk cetak sticker QR (per laptop atau massal via query `ids`).

**User** (`admin/user*.blade.php` + `admin/admin-user*.blade.php`)
- Dua scope dipisah di route (`defaults('scope', 'siswa')` / `'admin'`) → satu controller.
- Index: cari nama/username, filter role (scope admin), filter kelas/jurusan (scope siswa), `withCount` sesi aktif.
- Create/Update: `name`, `username` (unique), `password` (min 8, confirmed; opsional saat edit), `role` (siswa wajib `siswa`; admin boleh `admin`/`superadmin`), `kelas_id`/`jurusan_id` (wajib untuk siswa, `Rule::exists`).
- Proteksi: hanya superadmin yang bisa membuat/mengelola akun superadmin; tidak boleh ubah role akun sendiri; tidak boleh hapus akun sendiri; minimal 1 akun admin; tidak boleh hapus user yang terlibat peminjaman aktif. Saat password diganti: `remember_token` di-reset & seluruh sesi user itu dihapus (`DB::table('sessions')->where('user_id', …)->delete()`).

**Kelas & Jurusan** (`admin/kelas.blade.php`, `admin/jurusan.blade.php`)
- CRUD penuh (index/store/update/destroy) via controller terpisah `KelasController` / `JurusanController`. Data dipakai untuk form user & filter student di konfirmasi.

---

## 8. Notifikasi

Satu class: `app/Notifications/ReturnRequestNotification.php`.

- Berisi `title`, `message`, `url` (disimpan dalam kolom `data` JSON).
- Chatnel: **database** (tabel `notifications`).
- Dikirim ke:
  - **Admin** ketika siswa mengajukan pengembalian (`ReturnController@request`, via `Notification::send`).
  - **Siswa anggota** ketika admin menyetujui/ditolak (`AdminController@notifyStudents`, per-user `$student->notify(...)`).
- Tampil di **bel notifikasi** layout `app-siswa` (`dropdown-menu-notification`, maks 5). Tombol "tandai dibaca" memanggil `POST /notifications/read` (`markNotificationsRead` → `unreadNotifications->markAsRead()`).

---

## 9. QR Code (Dua Arah)

1. **Generate (server)**: `simplesoftwareio/simple-qrcode` (versi 2.0.0) → dibungkus `bacon/bacon-qr-code` (encoder + renderer). `AdminController::generateQrImage()` memakai facade `QrCode`, format PNG, 512px, margin 1, isi berisi `qr_token` (UUID) laptop. File disimpan `storage/app/public/qrcodes/` dan dipublikasikan lewat symlink `public/storage` (butuh `php artisan storage:link`). Diakses via `asset('storage/qrcodes/{token}.png')`.
2. **Scan (klien)**: `html5-qrcode` (file statis di `public/assets/libs/html5-qrcode/html5-qrcode.min.js`). Menggunakan kamera browser (`getUserMedia`, `facingMode: 'environment'`), mesin decode ZXing. Hasil decode (token) dikirim ke backend untuk dicocokkan.

---

## 10. Library & Paket

### 10.1 Dependency produksi (composer `require`)

| Paket | Versi | Peran |
|---|---|---|
| `php` | ^8.3 | Runtime |
| `laravel/framework` | ^13.17 (13.32) | Framework inti: routing, Eloquent, Blade, session, auth |
| `laravel/tinker` | ^3.0 | REPL untuk debugging (`php artisan tinker`) |
| `simplesoftwareio/simple-qrcode` | 2 | Generator QR PNG (facade `QrCode`) |

**Transitif penting** yang ikut ter-install (dipasang otomatis, tidak perlu di-require langsung):
- `bacon/bacon-qr-code` — encoder/renderer QR yang dipakai simple-qrcode.
- `brick/math` — aritmetika presisi tinggi (dipakai bacon-qr-code).
- `nesbot/carbon` — perpustakaan tanggal/waktu Eloquent.
- `league/flysystem` + `flysystem-local` — abstraksi storage/penyimpanan file.
- `ramsey/uuid` — pembangkit UUID (`Str::uuid()` untuk `qr_token`).
- Symfony components (`http-foundation`, `http-kernel`, `routing`, `console`, `mailer`, `mime`, `process`, `uid`, …) — fondasi HTTP, kernel, CLI artisan, mail.
- `guzzlehttp/guzzle` — HTTP client (untuk integrasi/perluasan).
- `psr/*` — standar antarmuka (container, log, http, event, clock).
- `monolog/monolog` — logging.
- `vlucas/phpdotenv` — baca `.env`.
- `laminas...`/`nette/schema`+`nette/utils` — dukungan serializer/validasi config.

### 10.2 Dependency development (composer `require-dev`)

| Paket | Versi | Peran |
|---|---|---|
| `phpunit/phpunit` | ^12.5 | Test runner PHPUnit |
| `mockery/mockery` | ^1.6 | Mocking untuk test |
| `fakerphp/faker` | ^1.23 | Generator data palsu untuk factory |
| `nunomaduro/collision` | ^8.6 | Pretty error/exception di CLI artisan |
| `laravel/pint` | ^1.27 | Code formatter (PSR-12 + Laravel preset), `composer test`→pint |
| `laravel/boost` | ^2.9 | MCP server: schema DB, log, search-docs (tooling IDE/agent) |
| `laravel/pail` | ^1.2 | Tail log live untuk CLI (`php artisan pail`) |
| `laravel/pao` | ^1.0 | Formatter output pengujian untuh agent (JSON) |
| `laravel/mcp`, `laravel/roster`, `laravel/agent-detector` | — | Tooling agent/MCP (dev-only) |

### 10.3 Library frontend (statis, bukan via npm)

| Library | Folder | Peran |
|---|---|---|
| Bootstrap 5 | `public/assets/libs/bootstrap/` | CSS/JS komponen UI, grid, navbar, dropdown, modal |
| Bootstrap Icons | `public/assets/libs/bootstrap-icons/` | Ikon (`bi bi-*`) |
| html5-qrcode | `public/assets/libs/html5-qrcode/` | Scanner QR via kamera |

CSS custom: `main.css` (layout admin), `siswa.css` (halaman siswa), `login-simple.css` / `login-admin.css` (pintu login). JS custom: `auth.js`, `dashboard.js`. Tidak ada bundler — semua `asset()` langsung.

---

## 11. Model & Relasi (Eloquent)

| Model | Relasi |
|---|---|
| `User` | `borrowings()` belongsToMany (pivot `borrowing_students`); `borrowingSessions()` hasMany (via `created_by`); `kelas()`/`jurusan()` belongsTo; helper `isAdmin()`, `isSuperAdmin()`, `isSiswa()`, `hasActiveBorrowingFor()`. Cast `password` → `hashed`. `#[Fillable]`/`#[Hidden]` memakai atribut PHP 8 Eloquent. |
| `Laptop` | `borrowings()` hasMany; `activeBorrowings()` (status aktif/menunggu); `activeBorrowing()`; `isAvailable()`; `qrImagePath()`. |
| `Borrowing` | `laptop()` belongsTo; `leader()` belongsTo (created_by); `students()` belongsToMany pivot `borrowing_students` (leader urutan pertama via `orderByRaw`); `returnRequests()` hasMany; `latestReturnRequest()`; `isActive()`. |
| `ReturnRequest` | `borrowing()` belongsTo; casts `requested_at`/`responded_at` datetime. |
| `BorrowingStudent` | model pivot (implicit). |
| `Kelas`, `Jurusan` | klasik; di-order by `nama` / `kode`. |

---

## 12. Keamanan

- **Hash password**: cast `hashed` (bcrypt) di `User`; update password user mengganti `remember_token` + menghapus sesi lama.
- **Otentikasi sesi aman**: `session()->regenerate()` saat login; `invalidate()` + `regenerateToken()` saat logout (anti session fixation).
- **Rate limiting**: `throttle:login` pada kedua endpoint login (anti brute force); dummy hash untuk meratakan waktu proses username tak dikenal.
- **Otorisasi**: middleware `admin`/`siswa` di grup route; `abort_if/abort_unless` di controller (mis. bukan anggota sesi → 403, sesi tidak aktif → 422).
- **Relasi hapus terproteksi**: laptop tidak bisa dihapus saat dipinjam; user tidak bisa dihapus saat terlibat sesi aktif; akun admin terakhir dilindungi; superadmin hanya dikelola superadmin.
- **Validation**: `Request::validate` di semua input; `Rule::exists`, `Rule::unique`, `Rule::in`; batas max 5 siswa.
- **Race condition**: peminjaman memakai `lockForUpdate()` di dalam `DB::transaction`.
- **Output**: Blade escape default; teks user di-render aman.

---

## 13. Pengujian (PHPUnit)

Menjalankan: `php artisan test` atau `PAO_DISABLE=1 vendor/bin/phpunit --debug` (untuk output native, nonaktifkan Pao). Database test: SQLite `:memory:` + `RefreshDatabase`.

| File test | Cakupan (jumlah test) |
|---|---|
| `AuthLoginTest` | 12 — login pintu siswa/admin, role mismatch, throttle, logout |
| `UserManagementTest` | 20 — CRUD user, proteksi superadmin, hapus diri, aktif |
| `LaptopCrudTest` | 9 — CRUD laptop, QR, blok hapus saat dipinjam |
| `BorrowingFlowTest` | 12 — scan/validasi, konfirmasi, store, max 5, race |
| `ReturnFlowTest` | 8 — ajukan, valida & proses, setujui/tolak, **catatan tampil di bukti** |
| `KelasManagementTest` | 7 — CRUD kelas |
| `JurusanManagementTest` | 7 — CRUD jurusan |
| `RoleAccessTest` | 6 — middleware/peran |

Pada baseline terakhir: **81 test / 338+ assertion** hijau, 0 skipped/risky/deprecation.

---

## 14. Menjalankan / Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate               # atau migrate --seed kalau mau data contoh
php artisan storage:link          # agar storage/qrcodes bisa diakses via public
php artisan serve                 # http://localhost:8000
```

Seeder (`DatabaseSeeder`) membuat: kelas X–XII, 7 jurusan, akun `admin`/`password`, `putracandra`/`password` (superadmin), ~42 siswa NIS `24710xx`/`password` (kelas XII RPL), dan 6 laptop lengkap dengan QR-nya. `ReferenceSeeder` saja bisa dipakai untuk data master ringan. Catatan: jangan jalankan seeder penuh berulang di DB dev yang sudah berisi data duplikat.

---

## 15. Catatan Arsitektur

- **Kontroller tipis-logika tebal di model/eloquent**; semua dipisah per domain (Auth, Borrowing, Return, Dashboard, Admin, Kelas, Jurusan).
- **No npm / no Vite**: seluruh aset yang dipakai adalah file statis langsung di `public/`.
- **Notifikasi database** dipakai sebagai pengganti email/real-time.
- **Timestamps** diatur Eloquent; kolom `requested_at`/`responded_at`/`borrowed_at`/`returned_at` dipakai sebagai catatan waktu bisnis (bukan updated_at default).
- **Satu halaman verifikasi memakai pagination** (10/halaman); dashboard memakai `take()` untuk ringkasan.
- **Relasi pivot**: satu sesi peminjaman bisa berisi 1–5 siswa; `created_by` menandai ketua/leader (tampil pertama di list anggota dan sebagai Penanggung Jawab di bukti).
```