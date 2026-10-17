# UC-08 Kelola Data Transportasi

## Ringkasan

UC-08 memungkinkan **Admin** melakukan operasi CRUD penuh terhadap data armada transportasi
pedesaan Jombang, termasuk menetapkan pengemudi sebagai operator kendaraan, dengan proteksi
penghapusan data relasi.

---

## Pemetaan UML → Implementasi Laravel

| Atribut UML (`Vehicle`) | Implementasi Laravel | Kolom / Keterangan |
|---|---|---|
| `operatorId` | `Angkutan.operator_id` | FK nullable ke `users.id`; harus role `pengemudi` |
| `vehicleNumber` | `Angkutan.vehicle_number` | Kolom baru, nullable di DB, unik |
| `licensePlate` | `Angkutan.license_plate` | Kolom baru, nullable di DB, unik |
| `type` | `Angkutan.jenis` | Kolom lama, nullable |
| `capacity` | `Angkutan.capacity` | Kolom baru, nullable di DB, unsigned int |
| `status` | `Angkutan.status_operasional` | Enum `aktif` / `tidak_aktif`, default `aktif` |
| *(legacy)* | `Angkutan.nama` | Wajib; kolom lama yang dipertahankan |
| *(legacy)* | `Angkutan.kode` | Wajib; unik; tidak disamakan dengan `vehicle_number` |
| Timestamps | `created_at`, `updated_at` | Dikelola Eloquent |

### Catatan penting
- Kolom UML baru (`operator_id`, `vehicle_number`, `license_plate`, `capacity`) bersifat **nullable
  pada database** demi kompatibilitas data lama, tetapi **diwajibkan melalui validasi controller**
  untuk setiap record baru.
- `kode` tetap unik dan tidak otomatis diisi dari `vehicle_number` maupun `license_plate`.
- Tabel tetap bernama `angkutans`; tidak ada tabel `vehicles` baru.

---

## File yang Terlibat

### Model
- [`app/Models/Angkutan.php`](../app/Models/Angkutan.php) — `$fillable`, cast `capacity`, relasi `operator()`, `rutes()`, `jadwals()`, `tarifs()`, `verifikasis()`
- [`app/Models/User.php`](../app/Models/User.php) — relasi `angkutans()` dengan FK `operator_id`

### Controller
- [`app/Http/Controllers/AngkutanController.php`](../app/Http/Controllers/AngkutanController.php) — semua operasi CRUD, validasi server-side, proteksi penghapusan, pemilihan operator `pengemudi`

### Route
- [`routes/web.php`](../routes/web.php) — resource route `angkutans.*` di bawah middleware `auth` + `role:admin`

### Halaman React/Inertia
| File | Deskripsi |
|---|---|
| [`resources/js/Pages/Angkutans/Index.jsx`](../resources/js/Pages/Angkutans/Index.jsx) | Daftar, pencarian, pagination, aksi |
| [`resources/js/Pages/Angkutans/Create.jsx`](../resources/js/Pages/Angkutans/Create.jsx) | Wrapper form tambah |
| [`resources/js/Pages/Angkutans/Edit.jsx`](../resources/js/Pages/Angkutans/Edit.jsx) | Wrapper form edit |
| [`resources/js/Pages/Angkutans/Show.jsx`](../resources/js/Pages/Angkutans/Show.jsx) | Halaman detail |
| [`resources/js/Pages/Angkutans/Form.jsx`](../resources/js/Pages/Angkutans/Form.jsx) | Komponen form berbagi (create & edit) |

### Migration
| File | Status | Keterangan |
|---|---|---|
| `2026_09_23_001728_create_angkutans_table.php` | ✅ Sudah dijalankan (batch 1) | Tabel awal dengan kolom legacy |
| `2026_10_10_001000_add_uml_vehicle_attributes_to_angkutans_table.php` | ✅ Sudah dijalankan (batch 3) | Menambahkan `operator_id`, `vehicle_number`, `license_plate`, `capacity` |
| `2026_10_17_000000_restrict_angkutan_dependent_foreign_keys.php` | ✅ Sudah dijalankan (batch 4) | Mengubah FK dari CASCADE → RESTRICT pada rutes/jadwals/tarifs/verifikasis |

### Factory & Test
- [`database/factories/AngkutanFactory.php`](../database/factories/AngkutanFactory.php) — membuat Angkutan dengan operator `pengemudi` secara otomatis
- [`tests/Feature/AngkutanCrudTest.php`](../tests/Feature/AngkutanCrudTest.php) — 13 test, 91 assertions

---

## Validasi dan Otorisasi

### Middleware
Route `angkutans.*` dilindungi oleh:
1. `auth` — pengguna yang belum login diarahkan ke `/login`
2. `role:admin` — role selain `admin` mendapat respons 403 Forbidden

Peran yang **ditolak**: `penumpang`, `pengemudi`, `super_admin`.

### Validasi Server (AngkutanController::validatedData)

| Field | Aturan |
|---|---|
| `nama` | required, string, max:255 |
| `kode` | required, string, max:255, unique (ignore saat update record sendiri) |
| `jenis` | required, string, max:255 |
| `status_operasional` | required, in:[aktif, tidak_aktif] |
| `operator_id` | required, integer, exists:users.id WHERE role='pengemudi' |
| `vehicle_number` | required, string, max:255, unique (ignore saat update record sendiri) |
| `license_plate` | required, string, max:255, unique (ignore saat update record sendiri) |
| `capacity` | required, integer, min:1, max:4294967295 |

**Catatan validasi:**
- `operator_id` divalidasi di server; frontend tidak dapat memalsukan role operator.
- Kolom nullable di DB tetap diwajibkan melalui validasi untuk data baru.

---

## Relasi dan Proteksi Penghapusan

### Relasi Angkutan

```
Angkutan → hasMany → Rute       (FK: angkutan_id, RESTRICT DELETE)
Angkutan → hasMany → Jadwal     (FK: angkutan_id, RESTRICT DELETE)
Angkutan → hasMany → Tarif      (FK: angkutan_id, RESTRICT DELETE)
Angkutan → hasMany → Verifikasi (FK: angkutan_id, RESTRICT DELETE)
Angkutan → belongsTo → User     (FK: operator_id, RESTRICT DELETE)
```

### Logika Penghapusan (AngkutanController::destroy)

1. Angkutan dikunci dengan `lockForUpdate()` di dalam transaksi database.
2. Controller memeriksa apakah ada rute, jadwal, tarif, atau verifikasi terkait.
3. Jika ada dependensi → lempar `ValidationException` dengan pesan jelas; angkutan tidak dihapus.
4. Jika tidak ada dependensi → hapus angkutan.
5. Constraint RESTRICT di database memberikan lapisan proteksi kedua terhadap race condition.

Frontend menampilkan konfirmasi `window.confirm()` sebelum mengirim permintaan DELETE, dan menampilkan pesan error dari sesi jika penghapusan gagal.

---

## Menjalankan Test dengan Aman

Test menggunakan `RefreshDatabase` dan menargetkan database `angkutan_jombang_test` (dikonfigurasi di `phpunit.xml`). Database test **tidak** menggunakan data produksi.

```bash
# Hanya UC-08 feature test
php artisan test tests/Feature/AngkutanCrudTest.php

# Semua feature test (termasuk regression)
php artisan test tests/Feature/

# Pemeriksaan sintaks PHP
php -l app/Http/Controllers/AngkutanController.php
php -l app/Models/Angkutan.php

# Daftar route
php artisan route:list --path=angkutans --no-ansi

# Build frontend
npm run build
```

---

## Status Migration

Semua migration telah dijalankan pada **kedua database** (`angkutan_jombang` dan `angkutan_jombang_test`):

| Batch | Migration |
|---|---|
| 1 | `create_angkutans_table` + tabel-tabel terkait |
| 2 | `align_rombogin_user_roles` |
| 3 | `add_uml_vehicle_attributes_to_angkutans_table` |
| 4 | `restrict_angkutan_dependent_foreign_keys` ← **migration baru UC-08** |

Migration batch 4 mengubah FK dari rutes, jadwals, tarifs, dan verifikasis dari `CASCADE DELETE` menjadi `RESTRICT DELETE`. Perubahan ini tidak memengaruhi baris data yang ada.

---

## Konflik dan Risiko yang Tersisa

Tidak ada konflik skema yang belum terselesaikan. Catatan:

- Jika di masa mendatang ada fitur yang sengaja ingin menghapus Angkutan beserta seluruh data relasi
  secara otomatis (misalnya "purge armada"), perlu migration tambahan untuk mengembalikan CASCADE
  atau logika soft-delete.
- Verifikasi masih menggunakan `status` sebagai `string` biasa (tidak `enum`); isi nilai tidak
  divalidasi oleh skema database. Tidak berkaitan langsung dengan UC-08 tetapi perlu diperhatikan
  pada UC lain.
