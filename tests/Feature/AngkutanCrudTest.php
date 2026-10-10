<?php

use App\Models\Angkutan;
use App\Models\Jadwal;
use App\Models\Rute;
use App\Models\Tarif;
use App\Models\User;
use App\Models\Verifikasi;
use Inertia\Testing\AssertableInertia as Assert;

function createAdminForAngkutanTests(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function validAngkutanPayload(?User $operator = null): array
{
    $operator ??= User::factory()->create(['role' => 'pengemudi']);

    return [
        'nama' => 'Angkutan Jombang',
        'kode' => 'ANG-101',
        'vehicle_number' => 'VH-101',
        'license_plate' => 'S 101 XX',
        'jenis' => 'Minibus',
        'capacity' => 12,
        'operator_id' => $operator->id,
        'status_operasional' => 'aktif',
    ];
}

it('renders the transport list and details for an Admin', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();

    $this->actingAs($admin)->get(route('angkutans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Angkutans/Index')
            ->has('angkutans.data', 1)
            ->where('angkutans.data.0.id', $angkutan->id));

    $this->actingAs($admin)->get(route('angkutans.show', $angkutan))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Angkutans/Show')
            ->where('angkutan.id', $angkutan->id)
            ->where('angkutan.operator.id', $angkutan->operator_id));
});

it('creates, updates, and deletes an angkutan without dependencies', function () {
    $admin = createAdminForAngkutanTests();
    $payload = validAngkutanPayload();

    $this->actingAs($admin)->post(route('angkutans.store'), $payload)
        ->assertRedirect(route('angkutans.index'))
        ->assertSessionHas('success');

    $angkutan = Angkutan::query()->where('kode', 'ANG-101')->firstOrFail();
    $this->assertDatabaseHas('angkutans', [
        'id' => $angkutan->id,
        'nama' => 'Angkutan Jombang',
        'vehicle_number' => 'VH-101',
        'license_plate' => 'S 101 XX',
        'operator_id' => $payload['operator_id'],
        'capacity' => 12,
    ]);

    $payload['nama'] = 'Angkutan Jombang Baru';
    $payload['vehicle_number'] = 'VH-102';
    $payload['license_plate'] = 'S 102 XX';

    $this->actingAs($admin)->put(route('angkutans.update', $angkutan), $payload)
        ->assertRedirect(route('angkutans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('angkutans', [
        'id' => $angkutan->id,
        'nama' => 'Angkutan Jombang Baru',
        'vehicle_number' => 'VH-102',
    ]);

    $this->actingAs($admin)->delete(route('angkutans.destroy', $angkutan))
        ->assertRedirect(route('angkutans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('angkutans', ['id' => $angkutan->id]);
});

it('allows an update that retains the record unique values', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();
    $payload = [
        'nama' => 'Nama diperbarui',
        'kode' => $angkutan->kode,
        'vehicle_number' => $angkutan->vehicle_number,
        'license_plate' => $angkutan->license_plate,
        'jenis' => $angkutan->jenis,
        'capacity' => $angkutan->capacity,
        'operator_id' => $angkutan->operator_id,
        'status_operasional' => $angkutan->status_operasional,
    ];

    $this->actingAs($admin)->put(route('angkutans.update', $angkutan), $payload)
        ->assertRedirect(route('angkutans.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('angkutans', [
        'id' => $angkutan->id,
        'nama' => 'Nama diperbarui',
        'kode' => $angkutan->kode,
        'vehicle_number' => $angkutan->vehicle_number,
        'license_plate' => $angkutan->license_plate,
    ]);
});

it('rejects updates that reuse another record vehicle number or license plate', function () {
    $admin = createAdminForAngkutanTests();
    $first = Angkutan::factory()->create();
    $second = Angkutan::factory()->create();
    $payload = [
        'nama' => $first->nama,
        'kode' => $first->kode,
        'vehicle_number' => $second->vehicle_number,
        'license_plate' => $first->license_plate,
        'jenis' => $first->jenis,
        'capacity' => $first->capacity,
        'operator_id' => $first->operator_id,
        'status_operasional' => $first->status_operasional,
    ];

    $this->actingAs($admin)->put(route('angkutans.update', $first), $payload)
        ->assertSessionHasErrors('vehicle_number');

    $payload['vehicle_number'] = $first->vehicle_number;
    $payload['license_plate'] = $second->license_plate;

    $this->actingAs($admin)->put(route('angkutans.update', $first), $payload)
        ->assertSessionHasErrors('license_plate');
});

it('requires every field needed to create an angkutan', function () {
    $admin = createAdminForAngkutanTests();
    $payload = array_fill_keys([
        'nama', 'kode', 'vehicle_number', 'license_plate', 'jenis',
        'capacity', 'operator_id', 'status_operasional',
    ], '');

    $this->actingAs($admin)->post(route('angkutans.store'), $payload)
        ->assertSessionHasErrors(array_keys($payload));
});

it('rejects invalid capacities including values outside the unsigned integer range', function () {
    $admin = createAdminForAngkutanTests();
    $operator = User::factory()->create(['role' => 'pengemudi']);

    foreach ([0, -1, '1.5', 4294967296] as $capacity) {
        $payload = validAngkutanPayload($operator);
        $payload['capacity'] = $capacity;

        $this->actingAs($admin)->post(route('angkutans.store'), $payload)
            ->assertSessionHasErrors('capacity');
    }
});

it('validates unique identifiers, positive capacity, status, and Pengemudi role', function () {
    $admin = createAdminForAngkutanTests();
    $existing = Angkutan::factory()->create();

    $duplicate = validAngkutanPayload();
    $duplicate['kode'] = $existing->kode;
    $duplicate['vehicle_number'] = $existing->vehicle_number;
    $duplicate['license_plate'] = $existing->license_plate;
    $duplicate['capacity'] = 0;
    $duplicate['status_operasional'] = 'beroperasi';
    $duplicate['operator_id'] = User::factory()->create(['role' => 'penumpang'])->id;

    $this->actingAs($admin)->post(route('angkutans.store'), $duplicate)
        ->assertSessionHasErrors([
            'kode',
            'vehicle_number',
            'license_plate',
            'capacity',
            'status_operasional',
            'operator_id',
        ]);
});

it('rejects deletion while an angkutan has dependent data', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();
    Rute::query()->create([
        'angkutan_id' => $angkutan->id,
        'asal' => 'Jombang',
        'tujuan' => 'Peterongan',
        'jalur' => 'Jombang - Peterongan',
    ]);

    $this->actingAs($admin)->delete(route('angkutans.destroy', $angkutan))
        ->assertSessionHasErrors('angkutan');

    $this->assertDatabaseHas('angkutans', ['id' => $angkutan->id]);
});

it('rejects deletion while an angkutan has a schedule', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();
    Jadwal::query()->create([
        'angkutan_id' => $angkutan->id,
        'hari' => 'Senin',
        'jam_berangkat' => '08:00:00',
        'jam_selesai' => '09:00:00',
    ]);

    $this->actingAs($admin)->delete(route('angkutans.destroy', $angkutan))
        ->assertSessionHasErrors('angkutan');

    $this->assertDatabaseHas('angkutans', ['id' => $angkutan->id]);
});

it('rejects deletion while an angkutan has a tariff', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();
    Tarif::query()->create([
        'angkutan_id' => $angkutan->id,
        'nominal' => 5000,
        'keterangan' => 'Tarif uji',
    ]);

    $this->actingAs($admin)->delete(route('angkutans.destroy', $angkutan))
        ->assertSessionHasErrors('angkutan');

    $this->assertDatabaseHas('angkutans', ['id' => $angkutan->id]);
});

it('rejects deletion while an angkutan has verification data', function () {
    $admin = createAdminForAngkutanTests();
    $angkutan = Angkutan::factory()->create();
    Verifikasi::query()->create([
        'angkutan_id' => $angkutan->id,
        'user_id' => $admin->id,
        'status' => 'menunggu',
        'catatan' => null,
        'verified_at' => null,
    ]);

    $this->actingAs($admin)->delete(route('angkutans.destroy', $angkutan))
        ->assertSessionHasErrors('angkutan');

    $this->assertDatabaseHas('angkutans', ['id' => $angkutan->id]);
});

it('allows management only to authenticated Admin accounts', function () {
    $this->get(route('angkutans.index'))->assertRedirect(route('login'));

    foreach (['penumpang', 'pengemudi', 'super_admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('angkutans.index'))
            ->assertForbidden();
    }
});

it('rejects operators that are not Pengemudi', function () {
    $admin = createAdminForAngkutanTests();
    $payload = validAngkutanPayload(User::factory()->create(['role' => 'admin']));

    $this->actingAs($admin)->post(route('angkutans.store'), $payload)
        ->assertSessionHasErrors('operator_id');
});
