# Authentication dan User Management ROMBONGIN

Implementasi mengikuti Laravel 12 + Breeze (Inertia/React) dan mempertahankan skema users yang sudah ada.

## Pemetaan use case

- **UC-01 Registrasi Pengguna:** `GET/POST /register`, `RegisteredUserController`, model `User`, tabel `users`. Registrasi publik selalu menetapkan role `penumpang` dan status `aktif`; role/status kiriman klien diabaikan.
- **UC-02 Login:** `GET/POST /login`, `LoginRequest`, session Laravel, dan `/dashboard`. Login hanya menerima akun berstatus `aktif`; sesi diregenerasi setelah login, dan logout menggunakan POST serta invalidasi sesi.
- **UC-09 Kelola Pengguna:** resource `users.*`, `UserController`, middleware `auth` + `role:super_admin`, halaman Inertia `Users/*`, dan Eloquent. Daftar dipaginasi; operasi mengubah role/status dan menghapus akun melindungi Super Admin aktif terakhir.

## Role dan status

Role database yang berlaku: `penumpang`, `pengemudi`, `admin`, `super_admin`. Status: `aktif`, `nonaktif`. Migration `2026_10_10_000000_align_rombogin_user_roles` mengubah nilai lama `masyarakat` → `penumpang` dan `operator` → `pengemudi`, serta default role menjadi `penumpang`. Status yang sudah ada dipertahankan.

## Route utama

- `GET/POST /register`, `GET/POST /login`, `POST /logout`
- `GET /dashboard` (wajib login; label dashboard mengikuti role)
- `GET/POST /users`, `GET /users/create`, `GET /users/{user}`, `GET /users/{user}/edit`, `PUT/PATCH /users/{user}`, `DELETE /users/{user}` (Super Admin aktif)

Akun pengujian development dapat disediakan lewat `php artisan db:seed --class=UserSeeder` setelah menilai seeder lain yang sudah terhubung ke `DatabaseSeeder`. Password development seeder: `password123`; jangan gunakan di produksi.

## Verifikasi yang dijalankan

- `php artisan --version`: Laravel Framework 12.69.2.
- `php artisan migrate:status`: migration sebelumnya berstatus Ran.
- `php artisan migrate --force`: migration pemetaan role berhasil dijalankan; status akhir batch 2.
- `php artisan route:list --except-vendor`: route auth, dashboard, dan CRUD users terdaftar.
- `php -l` pada controller, route, migration, dan test: lolos.
- `git diff --check`: lolos.
- `php database/scripts/prepare-test-database.php`: menyiapkan database `angkutan_jombang_test` terpisah.
- `php artisan test --compact`: 31 test passed (101 assertions); test memakai MySQL `angkutan_jombang_test`, bukan database development.
- `npm.cmd run build`: berhasil (`vite build`, 1,017 modul ditransformasi).

## Bukti screenshot yang disarankan

1. Form registrasi dan pesan validasi.
2. Login sebagai satu akun untuk masing-masing dari empat role dan dashboard yang tampil.
3. Daftar pengguna sebagai Super Admin, termasuk role/status.
4. Form tambah dan ubah pengguna.
5. Respons 403 saat Admin atau Penumpang membuka `/users`.
6. Pesan penolakan login untuk akun `nonaktif` dan validasi ketika mencoba menonaktifkan/menghapus Super Admin aktif terakhir.

Test otomatis dapat dijalankan ulang dengan `php database/scripts/prepare-test-database.php` diikuti `php artisan test --compact`. Test framework membersihkan dan membangun ulang tabel hanya pada database test tersebut.
