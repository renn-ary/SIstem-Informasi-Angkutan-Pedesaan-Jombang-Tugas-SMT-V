<?php

use App\Models\Angkutan;
use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\Rute;
use App\Models\Tarif;
use App\Models\TitikPemberhentian;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// ── Helper ─────────────────────────────────────────────────────────────────────

/**
 * Buat satu set data angkutan+rute+jadwal+tarif siap pakai untuk test.
 */
function buatPerjalanan(string $asal = 'Jombang', string $tujuan = 'Mojoagung', string $status = 'aktif'): array
{
    $angkutan = Angkutan::factory()->create([
        'status_operasional' => $status,
        'capacity'           => 12,
    ]);

    $rute = Rute::query()->create([
        'angkutan_id' => $angkutan->id,
        'asal'        => $asal,
        'tujuan'      => $tujuan,
        'jalur'       => "$asal - $tujuan",
    ]);

    TitikPemberhentian::query()->create([
        'rute_id' => $rute->id,
        'nama'    => "$asal Pusat",
        'urutan'  => 1,
    ]);
    TitikPemberhentian::query()->create([
        'rute_id' => $rute->id,
        'nama'    => "$tujuan Pusat",
        'urutan'  => 2,
    ]);

    $jadwal = Jadwal::query()->create([
        'angkutan_id'  => $angkutan->id,
        'hari'         => 'Senin',
        'jam_berangkat' => '08:00:00',
        'jam_selesai'  => '09:00:00',
    ]);

    $tarif = Tarif::query()->create([
        'angkutan_id' => $angkutan->id,
        'nominal'     => 10000,
        'keterangan'  => 'Tarif umum',
    ]);

    return compact('angkutan', 'rute', 'jadwal', 'tarif');
}

// ── UC-03: Cari Perjalanan ─────────────────────────────────────────────────────

it('menampilkan halaman pencarian tanpa hasil ketika belum ada query', function () {
    $this->get(route('perjalanan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->where('sudahMencari', false)
            ->where('rutes', null));
});

it('menampilkan hasil pencarian yang sesuai asal dan tujuan', function () {
    ['rute' => $rute] = buatPerjalanan('Jombang', 'Mojoagung');
    buatPerjalanan('Wonosalam', 'Pare'); // Data lain yang tidak seharusnya muncul.

    $this->get(route('perjalanan.index', ['asal' => 'Jombang', 'tujuan' => 'Mojoagung']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->where('sudahMencari', true)
            ->has('rutes.data', 1)
            ->where('rutes.data.0.id', $rute->id));
});

it('menampilkan empty state ketika pencarian tidak menemukan hasil', function () {
    $this->get(route('perjalanan.index', ['asal' => 'TidakAda', 'tujuan' => 'TidakAda']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->where('sudahMencari', true)
            ->has('rutes.data', 0));
});

it('mengembalikan error validasi ketika asal kosong', function () {
    $this->get(route('perjalanan.index', ['asal' => '', 'tujuan' => 'Mojoagung']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->has('errors.asal'));
});

it('mengembalikan error validasi ketika tujuan kosong', function () {
    $this->get(route('perjalanan.index', ['asal' => 'Jombang', 'tujuan' => '']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->has('errors.tujuan'));
});

it('mengembalikan error validasi ketika asal dan tujuan keduanya kosong', function () {
    $this->get(route('perjalanan.index', ['asal' => '', 'tujuan' => '']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->has('errors.asal')
            ->has('errors.tujuan'));
});

it('menyertakan relasi angkutan, jadwal, dan tarif pada hasil pencarian', function () {
    buatPerjalanan('Jombang', 'Mojoagung');

    $this->get(route('perjalanan.index', ['asal' => 'Jombang', 'tujuan' => 'Mojoagung']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Index')
            ->has('rutes.data.0.angkutan')
            ->has('rutes.data.0.angkutan.jadwals')
            ->has('rutes.data.0.angkutan.tarifs'));
});

it('dapat diakses tanpa autentikasi (publik)', function () {
    $this->get(route('perjalanan.index'))
        ->assertOk();
});

it('dapat diakses oleh pengguna yang sudah login', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->get(route('perjalanan.index'))
        ->assertOk();
});

// ── UC-04: Lihat Detail Perjalanan ────────────────────────────────────────────

it('menampilkan detail perjalanan dengan semua relasi', function () {
    ['rute' => $rute] = buatPerjalanan();

    $this->get(route('perjalanan.show', $rute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Show')
            ->where('rute.id', $rute->id)
            ->has('rute.angkutan')
            ->has('rute.angkutan.jadwals')
            ->has('rute.angkutan.tarifs')
            ->has('rute.titik_pemberhentians'));
});

it('mengembalikan 404 untuk rute perjalanan yang tidak ditemukan', function () {
    $this->get(route('perjalanan.show', 99999))
        ->assertNotFound();
});

it('menampilkan detail perjalanan tanpa autentikasi (publik)', function () {
    ['rute' => $rute] = buatPerjalanan();

    $this->get(route('perjalanan.show', $rute))
        ->assertOk();
});

it('tarif yang ditampilkan berasal dari database', function () {
    ['rute' => $rute, 'tarif' => $tarif] = buatPerjalanan();

    $this->get(route('perjalanan.show', $rute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rute.angkutan.tarifs.0.nominal', $tarif->nominal));
});

it('titik pemberhentian diurutkan berdasarkan kolom urutan', function () {
    ['rute' => $rute] = buatPerjalanan();

    $this->get(route('perjalanan.show', $rute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rute.titik_pemberhentians', 2));
});

// ── UC-05: Pesan Perjalanan ───────────────────────────────────────────────────

it('pengguna yang belum login diarahkan ke halaman login saat membuka form pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();

    $this->get(route('pemesanan.create', ['rute_id' => $rute->id, 'jadwal_id' => $jadwal->id]))
        ->assertRedirect(route('login'));
});

it('pengguna selain Penumpang tidak dapat membuka form pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();

    foreach (['pengemudi', 'admin', 'super_admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('pemesanan.create', ['rute_id' => $rute->id, 'jadwal_id' => $jadwal->id]))
            ->assertForbidden();
    }
});

it('Penumpang yang login dapat membuka halaman konfirmasi pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->get(route('pemesanan.create', ['rute_id' => $rute->id, 'jadwal_id' => $jadwal->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Perjalanan/Pesan')
            ->where('rute.id', $rute->id)
            ->where('jadwal.id', $jadwal->id));
});

it('Penumpang tidak dapat membuka konfirmasi untuk angkutan yang tidak aktif', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan(status: 'tidak_aktif');
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->get(route('pemesanan.create', ['rute_id' => $rute->id, 'jadwal_id' => $jadwal->id]))
        ->assertSessionHasErrors();
});

it('Penumpang dapat membuat pemesanan yang valid', function () {
    ['rute' => $rute, 'jadwal' => $jadwal, 'tarif' => $tarif] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 2,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('bookings', [
        'user_id'         => $penumpang->id,
        'schedule_id'     => $jadwal->id,
        'passenger_count' => 2,
        'total_fare'      => $tarif->nominal * 2,
        'booking_status'  => 'menunggu_pembayaran',
        'payment_status'  => 'menunggu_pembayaran',
    ]);
});

it('pemesanan menggunakan user_id dari sesi login, bukan dari input frontend', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $penumpangLain = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 1,
            // Percobaan manipulasi: mengirim user_id milik orang lain.
            'user_id'         => $penumpangLain->id,
        ])
        ->assertRedirect();

    // Booking harus terikat ke penumpang yang login, bukan penumpangLain.
    $this->assertDatabaseMissing('bookings', [
        'user_id' => $penumpangLain->id,
    ]);
    $this->assertDatabaseHas('bookings', [
        'user_id' => $penumpang->id,
    ]);
});

it('tarif dihitung dari database, bukan dari input frontend', function () {
    ['rute' => $rute, 'jadwal' => $jadwal, 'tarif' => $tarif] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 1,
            // Percobaan manipulasi: total_fare dari frontend diabaikan server.
            'total_fare'      => 1,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('bookings', [
        'total_fare' => $tarif->nominal * 1,
    ]);
});

it('menolak pemesanan untuk angkutan yang tidak aktif', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan(status: 'tidak_aktif');
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 1,
        ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('bookings', ['user_id' => $penumpang->id]);
});

it('menolak pemesanan dengan jumlah penumpang yang tidak valid', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    // Nol penumpang.
    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 0,
        ])
        ->assertSessionHasErrors('passenger_count');

    // Lebih dari 10.
    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 11,
        ])
        ->assertSessionHasErrors('passenger_count');

    $this->assertDatabaseMissing('bookings', ['user_id' => $penumpang->id]);
});

it('pemesanan memiliki status awal menunggu pembayaran', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 1,
        ]);

    $this->assertDatabaseHas('bookings', [
        'user_id'        => $penumpang->id,
        'booking_status' => 'menunggu_pembayaran',
        'payment_status' => 'menunggu_pembayaran',
    ]);
});

it('pemesanan diarahkan ke halaman transaksi setelah berhasil (integrasi UC-06)', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $response = $this->actingAs($penumpang)
        ->post(route('pemesanan.store'), [
            'rute_id'         => $rute->id,
            'jadwal_id'       => $jadwal->id,
            'passenger_count' => 1,
        ]);

    $booking = Booking::where('user_id', $penumpang->id)->first();
    $response->assertRedirect(route('transaksis.show', $booking));
});

it('pengguna yang belum login tidak dapat melakukan pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();

    $this->post(route('pemesanan.store'), [
        'rute_id'         => $rute->id,
        'jadwal_id'       => $jadwal->id,
        'passenger_count' => 1,
    ])->assertRedirect(route('login'));

    $this->assertDatabaseEmpty('bookings');
});

it('selain Penumpang tidak dapat melakukan pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();

    foreach (['pengemudi', 'admin', 'super_admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('pemesanan.store'), [
                'rute_id'         => $rute->id,
                'jadwal_id'       => $jadwal->id,
                'passenger_count' => 1,
            ])
            ->assertForbidden();
    }

    $this->assertDatabaseEmpty('bookings');
});

it('booking_code unik pada setiap pemesanan', function () {
    ['rute' => $rute, 'jadwal' => $jadwal] = buatPerjalanan();
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)->post(route('pemesanan.store'), [
        'rute_id'         => $rute->id,
        'jadwal_id'       => $jadwal->id,
        'passenger_count' => 1,
    ]);
    $this->actingAs($penumpang)->post(route('pemesanan.store'), [
        'rute_id'         => $rute->id,
        'jadwal_id'       => $jadwal->id,
        'passenger_count' => 1,
    ]);

    $codes = Booking::where('user_id', $penumpang->id)->pluck('booking_code');
    expect($codes->unique()->count())->toBe($codes->count());
});
