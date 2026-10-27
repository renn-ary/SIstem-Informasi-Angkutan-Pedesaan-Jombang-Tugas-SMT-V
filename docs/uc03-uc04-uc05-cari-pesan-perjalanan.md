# UC-03 Cari Perjalanan · UC-04 Lihat Detail Perjalanan · UC-05 Pesan Perjalanan

> **Implementasi:** Anggota 1  
> **Project:** Jombang Transit — Sistem Informasi Angkutan Pedesaan Jombang  
> **Tanggal:** 27 Oktober 2026

---

## A. Ringkasan Implementasi

Tiga use case berikut telah diimplementasikan secara penuh pada project Laravel 12 + React 18 + Inertia.js yang sudah ada, menggunakan struktur database dan model yang telah disepakati kelompok tanpa membuat tabel duplikat atau migration baru.

| UC | Status |
|---|---|
| UC-03 Cari Perjalanan | ✅ Selesai |
| UC-04 Lihat Detail Perjalanan | ✅ Selesai |
| UC-05 Pesan Perjalanan | ✅ Selesai |

---

## B. File yang Ditambahkan

| File | Fungsi |
|---|---|
| `app/Http/Controllers/PerjalananController.php` | Controller UC-03 (index) dan UC-04 (show) |
| `app/Http/Controllers/PemesananController.php` | Controller UC-05 (create + store) |
| `resources/js/Pages/Perjalanan/Index.jsx` | Halaman React UC-03 Cari Perjalanan |
| `resources/js/Pages/Perjalanan/Show.jsx` | Halaman React UC-04 Detail Perjalanan |
| `resources/js/Pages/Perjalanan/Pesan.jsx` | Halaman React UC-05 Konfirmasi Pemesanan |
| `tests/Feature/PerjalananTest.php` | 28 test Pest untuk UC-03, UC-04, UC-05 |

---

## C. File yang Diubah

| File | Perubahan | Alasan |
|---|---|---|
| `routes/web.php` | Tambah import + 4 route baru | Mendaftarkan endpoint UC-03/04/05 |
| `resources/js/Layouts/AuthenticatedLayout.jsx` | Tambah link "Cari Perjalanan" di nav desktop dan mobile | Penumpang dapat mengakses UC-03 dari navbar |

Tidak ada file milik ketua atau Anggota 2 yang dihapus atau ditimpa.

---

## D. Perubahan Database

**Tidak ada migration baru yang diperlukan.**

Struktur database yang sudah ada mencukupi sepenuhnya:

| Tabel | Digunakan Oleh |
|---|---|
| `rutes` (asal, tujuan, jalur, angkutan_id) | UC-03 pencarian, UC-04 detail |
| `angkutans` (nama, kode, jenis, status_operasional, capacity) | UC-03/04/05 |
| `jadwals` (angkutan_id, hari, jam_berangkat, jam_selesai) | UC-03/04/05 |
| `tarifs` (angkutan_id, nominal, keterangan) | UC-03/04/05 kalkulasi tarif |
| `titik_pemberhentians` (rute_id, nama, urutan) | UC-04 detail rute |
| `bookings` (booking_code, user_id, schedule_id, passenger_count, total_fare, booking_status, payment_status, booked_at) | UC-05 menyimpan pemesanan |

---

## E. Route dan Middleware

```
# UC-03 & UC-04 — Publik (tidak memerlukan autentikasi)
GET  /perjalanan              perjalanan.index    PerjalananController@index
GET  /perjalanan/{rute}       perjalanan.show     PerjalananController@show

# UC-05 — Hanya Penumpang yang sudah login
GET  /pemesanan/konfirmasi    pemesanan.create    PemesananController@create   [auth, role:penumpang]
POST /pemesanan               pemesanan.store     PemesananController@store    [auth, role:penumpang]
```

Middleware `role:penumpang` sudah tersedia di project (tidak dimodifikasi).

---

## F. Catatan Integrasi

### 1. Bagaimana UC-03 Terhubung dengan UC-04
- Dari halaman hasil pencarian UC-03 (`resources/js/Pages/Perjalanan/Index.jsx`), setiap kartu rute yang ditemukan memiliki tombol **"Lihat Detail & Jadwal"**.
- Tombol ini mengarahkan pengguna ke route `perjalanan.show` (`GET /perjalanan/{rute}`) dengan menyertakan parameter `asal` dan `tujuan` via query string (`?asal=...&tujuan=...`).
- Parameter query pencarian ini ditangkap oleh `PerjalananController@show` dan diteruskan ke view UC-04 sebagai `querySebelumnya`.
- Di view UC-04 (`resources/js/Pages/Perjalanan/Show.jsx`), terdapat tombol navigasi **"Kembali ke Hasil Pencarian"** yang mempertahankan nilai asal dan tujuan pencarian sebelumnya, sehingga pengguna tidak perlu mengetik ulang query pencariannya.

### 2. Bagaimana UC-04 Terhubung dengan UC-05
- Pada halaman detail rute UC-04, sistem menampilkan tabel jadwal keberangkatan dan tarif resmi yang berlaku.
- Jika pengguna telah login dengan role `Penumpang` dan status operasional angkutan adalah `aktif`, pada setiap baris jadwal yang tersedia ditampilkan tombol **"Pesan Perjalanan"**.
- Tombol ini mengarahkan ke form konfirmasi pemesanan UC-05 (`GET /pemesanan/konfirmasi?rute_id=...&jadwal_id=...`).
- Jika pengguna belum login, sistem menampilkan tombol ajakan login yang mengarahkan ke halaman `/login`.
- Jika status operasional angkutan `tidak_aktif` atau `perbaikan`, tombol pemesanan dinonaktifkan dan digantikan dengan badge status peringatan.

### 3. Bagaimana UC-05 Terhubung dengan UC-06 Milik Anggota 2
- Setelah Penumpang memeriksa ringkasan pesanan dan menekan tombol konfirmasi pada halaman UC-05 (`resources/js/Pages/Perjalanan/Pesan.jsx`), form mengirimkan request `POST /pemesanan` ke `PemesananController@store`.
- Server memvalidasi seluruh input, memastikan angkutan aktif, mengambil nominal tarif dari database, dan menyimpan data `Booking` secara aman di dalam `DB::transaction()`.
- Setelah pemesanan tersimpan, `PemesananController@store` langsung me-redirect Penumpang ke halaman detail transaksi UC-06:
  ```php
  return redirect()->route('transaksis.show', $booking)
      ->with('success', 'Pemesanan berhasil dibuat. Silakan selesaikan pembayaran.');
  ```
- **Kontrak data yang dipenuhi UC-05 untuk UC-06:**
  - `bookings.schedule_id` → FK ke `jadwals.id` ✅
  - `bookings.user_id` → ID penumpang dari sesi login terautentikasi (`$request->user()->id`) ✅
  - `bookings.booking_status` → `'menunggu_pembayaran'` (`Booking::STATUS_MENUNGGU_PEMBAYARAN`) ✅
  - `bookings.payment_status` → `'menunggu_pembayaran'` (`Booking::PEMBAYARAN_MENUNGGU`) ✅
  - `bookings.booking_code` → Format unik `BK-XXXXXX` ✅
  - `bookings.total_fare` → Dihitung dari `tarifs.nominal × passenger_count` di sisi server ✅
- Penumpang langsung berada pada antarmuka pembayaran UC-06 tanpa ada broken link ataupun ketidaksesuaian data.

---

## G. Hasil Eksekusi Test

### 1. Command yang Dijalankan
```bash
php artisan test tests/Feature/PerjalananTest.php --env=testing
```

### 2. Output Eksekusi Test
```
PASS  Tests\Feature\PerjalananTest
✓ it menampilkan halaman pencarian tanpa hasil ketika belum ada query
✓ it menampilkan hasil pencarian yang sesuai asal dan tujuan
✓ it menampilkan empty state ketika pencarian tidak menemukan hasil
✓ it mengembalikan error validasi ketika asal kosong
✓ it mengembalikan error validasi ketika tujuan kosong
✓ it mengembalikan error validasi ketika asal dan tujuan keduanya kosong
✓ it menyertakan relasi angkutan, jadwal, dan tarif pada hasil pencarian
✓ it dapat diakses tanpa autentikasi (publik)
✓ it dapat diakses oleh pengguna yang sudah login
✓ it menampilkan detail perjalanan dengan semua relasi
✓ it mengembalikan 404 untuk rute perjalanan yang tidak ditemukan
✓ it menampilkan detail perjalanan tanpa autentikasi (publik)
✓ it tarif yang ditampilkan berasal dari database
✓ it titik pemberhentian diurutkan berdasarkan kolom urutan
✓ it pengguna yang belum login diarahkan ke halaman login saat membuka form pemesanan
✓ it pengguna selain Penumpang tidak dapat membuka form pemesanan
✓ it Penumpang yang login dapat membuka halaman konfirmasi pemesanan
✓ it Penumpang tidak dapat membuka konfirmasi untuk angkutan yang tidak aktif
✓ it Penumpang dapat membuat pemesanan yang valid
✓ it pemesanan menggunakan user_id dari sesi login, bukan dari input frontend
✓ it tarif dihitung dari database, bukan dari input frontend
✓ it menolak pemesanan untuk angkutan yang tidak aktif
✓ it menolak pemesanan dengan jumlah penumpang yang tidak valid
✓ it pemesanan memiliki status awal menunggu pembayaran
✓ it pemesanan diarahkan ke halaman transaksi setelah berhasil (integrasi UC-06)
✓ it pengguna yang belum login tidak dapat melakukan pemesanan
✓ it selain Penumpang tidak dapat melakukan pemesanan
✓ it booking_code unik pada setiap pemesanan

Tests:    28 passed (178 assertions)
Duration: 11.35s
```

Semua 28 test case mencakup seluruh skenario positif, validasi input, hak akses/otorisasi role, dan penanganan edge case sesuai Activity Diagram.

---

## H. Masalah yang Ditemukan dan Solusinya

### 1. Perbedaan UML / Perencanaan vs Kondisi Nyata Codebase
- **Relasi `Angkutan` dan `Rute`:** Pada ERD/UML konseptual, `rutes` memiliki foreign key `angkutan_id`. Namun pada model `Angkutan`, relasi `rutes()` telah didefinisikan sebagai `hasMany(Rute::class)`. Kita menggunakan relasi yang sudah ada ini secara konsisten tanpa merubah struktur tabel.
- **Relasi `Booking` ke Jadwal:** Kolom foreign key pada tabel `bookings` adalah `schedule_id` yang merujuk ke tabel `jadwals`. Di model `Booking`, relasi didefinisikan sebagai `jadwal()`. Implementasi UC-05 secara tepat mengisi `schedule_id` dan menjaga relasi ini tetap valid.

### 2. Isu Teknis yang Ditemukan Selama Implementasi
- **Eager Loading Closure Type-Hint:** Pada Laravel 12, parameter closure pada eager loading `with(['titikPemberhentians' => fn ($q) => ...])` meneruskan instance relasi Eloquent (`HasMany`/`BelongsTo`) sebelum diteruskan ke query builder. Memberikan type-hint kaku `Builder $q` menyebabkan `TypeError`. **Solusi:** Menghapus type-hint spesifik pada closure eager loading sehingga fleksibel menerima instance builder maupun relation.
- **Vite Manifest Test Environment:** Pengujian fitur Inertia pada awalnya menghasilkan `ViteException: Unable to locate file in Vite manifest` karena file JSX baru belum di-build ke direktori `public/build/`. **Solusi:** Menjalankan `npm run build` sehingga manifest Vite terdaftar lengkap, setelah itu seluruh pengujian Inertia render sukses 100%.

### 3. Asumsi yang Diambil Selama Pengerjaan
- **Pencarian Publik:** Sesuai Activity Diagram UC-03, pengguna umum (tamu/guest) dapat mencari perjalanan dan melihat detail rute tanpa perlu login terlebih dahulu. Autentikasi dan pengecekan role `penumpang` baru diwajibkan saat pengguna ingin melakukan pemesanan (UC-05).
- **Tarif Flat per Penumpang:** Karena tabel `tarifs` mengaitkan nominal langsung ke `angkutan_id` (tanpa jarak per kilometer), perhitungan biaya pemesanan didasarkan pada rumus `nominal × passenger_count`.
- **Batas Maksimal Penumpang:** Ditentukan validasi `1` hingga `10` orang per satu kali pemesanan untuk mengakomodasi kapasitas standar armada angkutan pedesaan di Jombang.

---

## I. Status Git
File yang ditambahkan dan dimodifikasi dibiarkan uncommitted pada working directory sesuai instruksi:
```
M  resources/js/Layouts/AuthenticatedLayout.jsx
M  routes/web.php
?? app/Http/Controllers/PemesananController.php
?? app/Http/Controllers/PerjalananController.php
?? docs/uc03-uc04-uc05-cari-pesan-perjalanan.md
?? resources/js/Pages/Perjalanan/
?? tests/Feature/PerjalananTest.php
```

---

## K. Arsitektur Ringkas

```
UC-03 Cari Perjalanan
  GET /perjalanan?asal=X&tujuan=Y
  → PerjalananController::index()
  → Rute::query()->where(asal LIKE X)->where(tujuan LIKE Y)
      ->with([angkutan, angkutan.jadwals, angkutan.tarifs, titikPemberhentians])
      ->paginate(10)
  → Inertia::render('Perjalanan/Index', [...])

UC-04 Lihat Detail Perjalanan
  GET /perjalanan/{rute}
  → PerjalananController::show(Rute $rute)
  → $rute->load([angkutan.jadwals, angkutan.tarifs, titikPemberhentians])
  → Inertia::render('Perjalanan/Show', [rute, querySebelumnya])

UC-05 Pesan Perjalanan
  GET  /pemesanan/konfirmasi?rute_id=X&jadwal_id=Y   [auth, role:penumpang]
  → PemesananController::create()
  → Tampil form konfirmasi

  POST /pemesanan   [auth, role:penumpang]
  → PemesananController::store()
  → Validasi → Cek status aktif → Hitung tarif dari DB
  → DB::transaction(fn => Booking::create([...]))
  → redirect(transaksis.show, $booking)  ← integrasi UC-06
```
