<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Users/Index', [
            'users' => User::query()->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
                ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query
                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('id')->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Users/Create', ['roles' => $this->roles()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($this->roles())],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return Inertia::render('Users/Show', ['user' => $user->only(['id', 'name', 'email', 'role', 'status', 'created_at'])]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', ['user' => $user->only(['id', 'name', 'email', 'role', 'status']), 'roles' => $this->roles()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($this->roles())],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);
        if (blank($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);

        DB::transaction(function () use ($user, $data, $request) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->preserveActiveSuperAdmin($lockedUser, $data['role'], $data['status'], $request->user());
            $lockedUser->update($data);
        });
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        DB::transaction(function () use ($user, $request) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->is($request->user())) {
                throw ValidationException::withMessages(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
            }
            $this->preserveActiveSuperAdmin($lockedUser, null, null, $request->user());
            $lockedUser->delete();
        });
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    private function roles(): array
    {
        return ['penumpang', 'pengemudi', 'admin', 'super_admin'];
    }

    private function preserveActiveSuperAdmin(User $target, ?string $newRole, ?string $newStatus, User $actor): void
    {
        $removesActiveSuperAdmin = $target->role === 'super_admin' && $target->status === 'aktif'
            && (($newRole !== null && $newRole !== 'super_admin') || ($newStatus !== null && $newStatus !== 'aktif') || $newRole === null);
        if (! $removesActiveSuperAdmin) return;

        if ($target->is($actor)) {
            throw ValidationException::withMessages(['user' => 'Anda tidak dapat menonaktifkan, mengubah role, atau menghapus akun sendiri.']);
        }
        $activeAdmins = User::query()->where('role', 'super_admin')->where('status', 'aktif')->count();
        if ($activeAdmins <= 1) {
            throw ValidationException::withMessages(['user' => 'Minimal harus ada satu Super Admin aktif.']);
        }
    }
}
