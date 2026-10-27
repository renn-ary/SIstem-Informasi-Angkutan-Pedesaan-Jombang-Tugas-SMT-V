<?php

use App\Models\Angkutan;
use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function buatTransaksiUntuk(User $penumpang, string $statusOperasional = 'aktif'): Booking
{
    $angkutan = Angkutan::factory()->create(['status_operasional' => $statusOperasional]);

    $jadwal = Jadwal::query()->create([
        'angkutan_id' => $angkutan->id,
        'hari' => 'Senin',
        'jam_berangkat' => '08:00:00',
        'jam_selesai' => '09:00:00',
    ]);

    return Booking::query()->create([
        'booking_code' => 'BK-'.fake()->unique()->numerify('######'),
        'user_id' => $penumpang->id,
        'schedule_id' => $jadwal->id,
        'passenger_count' => 2,
        'total_fare' => 20000,
        'booking_status' => 'menunggu_pembayaran',
        'payment_status' => 'menunggu_pembayaran',
        'booked_at' => now(),
    ]);
}

it('menampilkan hanya transaksi milik Penumpang yang sedang login', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);
    buatTransaksiUntuk(User::factory()->create(['role' => 'penumpang']));

    $this->actingAs($penumpang)->get(route('transaksis.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Transaksis/Index')
            ->has('transaksis.data', 1)
            ->where('transaksis.data.0.id', $transaksi->id));
});

it('hanya menampilkan detail transaksi kepada pemiliknya', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $penumpangLain = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);

    $this->actingAs($penumpang)->get(route('transaksis.show', $transaksi))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Transaksis/Show')
            ->where('transaksi.id', $transaksi->id));

    $this->actingAs($penumpangLain)->get(route('transaksis.show', $transaksi))
        ->assertForbidden();
});

it('memproses pembayaran dan mengonfirmasi transaksi', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);

    $this->actingAs($penumpang)
        ->post(route('transaksis.bayar', $transaksi), ['method' => 'tunai'])
        ->assertRedirect(route('transaksis.show', $transaksi))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'payment_status' => 'lunas',
        'booking_status' => 'dikonfirmasi',
    ]);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $transaksi->id,
        'method' => 'tunai',
        'status' => 'lunas',
    ]);
});

it('menandai pembayaran gagal bila angkutan terkait tidak beroperasi', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang, 'tidak_aktif');

    $this->actingAs($penumpang)
        ->post(route('transaksis.bayar', $transaksi), ['method' => 'transfer'])
        ->assertSessionHasErrors('transaksi');

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'payment_status' => 'gagal',
        'booking_status' => 'menunggu_pembayaran',
    ]);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $transaksi->id,
        'status' => 'gagal',
    ]);
});

it('menolak pembayaran pada transaksi yang sudah lunas', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);
    $transaksi->update(['payment_status' => 'lunas', 'booking_status' => 'dikonfirmasi']);

    $this->actingAs($penumpang)
        ->post(route('transaksis.bayar', $transaksi), ['method' => 'tunai'])
        ->assertSessionHasErrors('transaksi');

    $this->assertDatabaseMissing('payments', ['booking_id' => $transaksi->id]);
});

it('mewajibkan metode pembayaran yang dikenal sistem', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);

    $this->actingAs($penumpang)
        ->post(route('transaksis.bayar', $transaksi), ['method' => ''])
        ->assertSessionHasErrors('method');

    $this->actingAs($penumpang)
        ->post(route('transaksis.bayar', $transaksi), ['method' => 'qris'])
        ->assertSessionHasErrors('method');

    $this->assertDatabaseMissing('payments', ['booking_id' => $transaksi->id]);
});

it('membatasi halaman transaksi hanya untuk Penumpang terautentikasi', function () {
    $this->get(route('transaksis.index'))->assertRedirect(route('login'));

    foreach (['pengemudi', 'admin', 'super_admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('transaksis.index'))
            ->assertForbidden();
    }
});

it('membatalkan transaksi yang masih menunggu pembayaran', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);

    $this->actingAs($penumpang)
        ->post(route('transaksis.batalkan', $transaksi))
        ->assertRedirect(route('transaksis.show', $transaksi))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'booking_status' => 'dibatalkan',
    ]);
});

it('membatalkan transaksi yang sudah dikonfirmasi', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);
    $transaksi->update(['payment_status' => 'lunas', 'booking_status' => 'dikonfirmasi']);

    $this->actingAs($penumpang)
        ->post(route('transaksis.batalkan', $transaksi))
        ->assertRedirect(route('transaksis.show', $transaksi))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'booking_status' => 'dibatalkan',
    ]);
});

it('menolak pembatalan transaksi yang sudah dibatalkan', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);
    $transaksi->update(['booking_status' => 'dibatalkan']);

    $this->actingAs($penumpang)
        ->post(route('transaksis.batalkan', $transaksi))
        ->assertSessionHasErrors('transaksi');

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'booking_status' => 'dibatalkan',
    ]);
});

it('menolak pembatalan transaksi milik Penumpang lain', function () {
    $pemilik = User::factory()->create(['role' => 'penumpang']);
    $penumpangLain = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($pemilik);

    $this->actingAs($penumpangLain)
        ->post(route('transaksis.batalkan', $transaksi))
        ->assertForbidden();

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'booking_status' => 'menunggu_pembayaran',
    ]);
});

it('menolak pembatalan transaksi oleh selain Penumpang', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $transaksi = buatTransaksiUntuk($penumpang);

    foreach (['pengemudi', 'admin', 'super_admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('transaksis.batalkan', $transaksi))
            ->assertForbidden();
    }

    $this->assertDatabaseHas('bookings', [
        'id' => $transaksi->id,
        'booking_status' => 'menunggu_pembayaran',
    ]);
});
