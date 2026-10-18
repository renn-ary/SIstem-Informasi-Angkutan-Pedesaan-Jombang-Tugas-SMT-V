<?php

use App\Models\Angkutan;
use App\Models\Report;
use App\Models\Rute;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function buatRuteUntukLaporan(): Rute
{
    $angkutan = Angkutan::factory()->create();

    return Rute::query()->create([
        'angkutan_id' => $angkutan->id,
        'asal' => 'Jombang',
        'tujuan' => 'Diwek',
        'jalur' => 'Jombang - Diwek',
    ]);
}

it('menyimpan laporan layanan yang dikirim Penumpang', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $rute = buatRuteUntukLaporan();

    $this->actingAs($penumpang)->post(route('reports.store'), [
        'route_id' => $rute->id,
        'category' => 'Pelayanan',
        'description' => 'Angkutan datang terlambat dari jadwal.',
        'latitude' => -7.5461,
        'longitude' => 112.2345,
    ])->assertRedirect(route('reports.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('reports', [
        'user_id' => $penumpang->id,
        'route_id' => $rute->id,
        'category' => 'Pelayanan',
        'description' => 'Angkutan datang terlambat dari jadwal.',
        'status' => 'menunggu_tindak_lanjut',
    ]);
});

it('menolak laporan tanpa isi laporan', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);

    $this->actingAs($penumpang)->post(route('reports.store'), ['description' => ''])
        ->assertSessionHasErrors('description');

    $this->assertDatabaseCount('reports', 0);
});

it('menampilkan hanya laporan milik Penumpang yang sedang login', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $penumpangLain = User::factory()->create(['role' => 'penumpang']);
    Report::factory()->create(['user_id' => $penumpang->id]);
    Report::factory()->create(['user_id' => $penumpangLain->id]);

    $this->actingAs($penumpang)->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 1)
            ->where('canManage', false)
            ->where('canCreate', true));
});

it('menampilkan seluruh laporan kepada Admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Report::factory()->count(2)->create();

    $this->actingAs($admin)->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 2)
            ->where('canManage', true)
            ->where('canCreate', false));
});

it('memperbolehkan Admin menindaklanjuti laporan', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $report = Report::factory()->create();

    $this->actingAs($admin)->put(route('reports.tindak-lanjut', $report), [
        'status' => 'ditindaklanjuti',
        'response' => 'Sudah ditindaklanjuti oleh petugas.',
    ])->assertRedirect(route('reports.show', $report))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'ditindaklanjuti',
        'response' => 'Sudah ditindaklanjuti oleh petugas.',
    ]);
});

it('menolak tindak lanjut dari Penumpang', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $report = Report::factory()->create(['user_id' => $penumpang->id]);

    $this->actingAs($penumpang)->put(route('reports.tindak-lanjut', $report), [
        'status' => 'ditindaklanjuti',
        'response' => 'Coba ubah sendiri.',
    ])->assertForbidden();

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'menunggu_tindak_lanjut',
    ]);
});

it('menolak Penumpang melihat detail laporan milik orang lain', function () {
    $penumpang = User::factory()->create(['role' => 'penumpang']);
    $penumpangLain = User::factory()->create(['role' => 'penumpang']);
    $report = Report::factory()->create(['user_id' => $penumpangLain->id]);

    $this->actingAs($penumpang)->get(route('reports.show', $report))->assertForbidden();

    $this->actingAs($penumpangLain)->get(route('reports.show', $report))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Show')
            ->where('report.id', $report->id)
            ->where('canManage', false));
});

it('menolak Admin membuat laporan layanan', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('reports.create'))->assertForbidden();
    $this->actingAs($admin)->post(route('reports.store'), ['description' => 'Laporan admin'])
        ->assertForbidden();

    $this->assertDatabaseCount('reports', 0);
});

it('mengalihkan tamu ke halaman login', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('reports.create'))->assertRedirect(route('login'));
});
