<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration creates an active passenger and ignores submitted privilege fields', function () {
    $this->post('/register', [
        'name' => 'Rina', 'email' => 'rina@example.test', 'password' => 'password123',
        'password_confirmation' => 'password123', 'role' => 'super_admin', 'status' => 'nonaktif',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'rina@example.test')->firstOrFail();
    expect($user->role)->toBe('penumpang')->and($user->status)->toBe('aktif')->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('duplicate registration email is rejected', function () {
    User::factory()->create(['email' => 'used@example.test']);
    $this->from('/register')->post('/register', [
        'name' => 'Another', 'email' => 'used@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

test('inactive users cannot authenticate', function () {
    User::factory()->create(['email' => 'inactive@example.test', 'status' => 'nonaktif']);
    $this->post('/login', ['email' => 'inactive@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('only active super admins can access user management', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    foreach (['penumpang', 'pengemudi', 'admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('users.index'))->assertForbidden();
    }
    $root = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($root)->get(route('users.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Users/Index'));
});

test('dashboard identifies each supported role', function () {
    foreach ([
        'penumpang' => 'penumpang',
        'pengemudi' => 'pengemudi',
        'admin' => 'admin',
        'super_admin' => 'super-admin',
    ] as $role => $dashboardRole) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')->where('dashboardRole', $dashboardRole));
    }
});

test('user management validates fields and never exposes password hashes', function () {
    $root = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($root)->post(route('users.store'), [
        'name' => '', 'email' => 'not-an-email', 'password' => 'short', 'password_confirmation' => 'different', 'role' => 'root', 'status' => 'suspended',
    ])->assertSessionHasErrors(['name', 'email', 'password', 'role', 'status']);

    $person = User::factory()->create(['password' => 'secret-password']);
    $this->actingAs($root)->get(route('users.show', $person))
        ->assertInertia(fn ($page) => $page->component('Users/Show')->missing('user.password'));
});

test('super admin can create update and delete a user', function () {
    $root = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($root)->post(route('users.store'), [
        'name' => 'Driver', 'email' => 'driver@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'pengemudi', 'status' => 'aktif',
    ])->assertRedirect(route('users.index'));
    $driver = User::where('email', 'driver@example.test')->firstOrFail();
    $this->put(route('users.update', $driver), [
        'name' => 'Updated Driver', 'email' => 'driver@example.test', 'password' => '', 'password_confirmation' => '', 'role' => 'admin', 'status' => 'nonaktif',
    ])->assertRedirect(route('users.index'));
    expect($driver->fresh()->role)->toBe('admin')->and($driver->fresh()->status)->toBe('nonaktif');
    $this->delete(route('users.destroy', $driver))->assertRedirect(route('users.index'));
    $this->assertDatabaseMissing('users', ['id' => $driver->id]);
});

test('last active super admin cannot be disabled demoted or deleted', function () {
    $root = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($root)->put(route('users.update', $root), [
        'name' => $root->name, 'email' => $root->email, 'role' => 'admin', 'status' => 'aktif', 'password' => '', 'password_confirmation' => '',
    ])->assertSessionHasErrors('user');
    $this->delete(route('users.destroy', $root))->assertSessionHasErrors('user');
    expect($root->fresh()->role)->toBe('super_admin')->and($root->fresh()->status)->toBe('aktif');
});
