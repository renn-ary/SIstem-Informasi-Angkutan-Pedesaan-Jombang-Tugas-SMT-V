# UC-08 Kelola Data Transportasi

## Pemetaan UML ke Laravel

| UML/ERD | Implementasi |
|---|---|
| `Vehicle` | Model `Angkutan`, tabel `angkutans`; tidak membuat tabel `vehicles` |
| `operator_id` | FK nullable ke `users.id`; input baru wajib menunjuk user ber-role `pengemudi` |
| `vehicle_number` | Kolom baru nullable dan unique; wajib melalui aplikasi untuk data baru |
| `license_plate` | Kolom baru nullable dan unique; wajib melalui aplikasi untuk data baru |
| `type` | Dipetakan ke kolom `jenis` yang sudah ada |
| `capacity` | Kolom baru nullable; input baru integer positif |
| `status` | Dipetakan ke `status_operasional` yang sudah ada; pilihan `aktif`/`tidak_aktif` |
| Kolom legacy | `nama` dan `kode` tetap wajib; `kode` tetap unique dan tidak dipetakan ke nomor kendaraan atau plat nomor |

## Akses

Route resource `angkutans.*` hanya dapat diakses oleh akun terautentikasi dengan role `admin`. Penumpang, Pengemudi, dan Super Admin ditolak untuk use case ini sesuai asosiasi UC-08 pada Use Case Diagram.

## Integritas dan penghapusan

Migration menambahkan `operator_id`, `vehicle_number`, `license_plate`, dan `capacity` tanpa mengubah kolom lama atau foreign key relasi yang sudah ada. Operator dihubungkan ke `users.id` dengan penghapusan restricted. Data angkutan yang masih memiliki rute, jadwal, tarif, atau verifikasi tidak dapat dihapus.

Kolom tambahan nullable pada database untuk kompatibilitas data lama. Form tambah/edit mewajibkan atribut kendaraan baru dan operator. Tidak ada nilai yang diisi otomatis dari `nama` atau `kode`.

## Pengujian

Feature test ada di `tests/Feature/AngkutanCrudTest.php`. Test menggunakan database yang ditentukan konfigurasi PHPUnit (`angkutan_jombang_test`) karena feature suite menggunakan `RefreshDatabase`. Migration UC-08 belum dijalankan; tinjau migration baru sebelum menjalankan migration maupun test yang membangun ulang database test.
