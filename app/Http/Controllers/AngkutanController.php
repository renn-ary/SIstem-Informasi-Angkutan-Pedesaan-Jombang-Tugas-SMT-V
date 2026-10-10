<?php

namespace App\Http\Controllers;

use App\Models\Angkutan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AngkutanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('Angkutans/Index', [
            'angkutans' => Angkutan::query()
                ->with('operator:id,name,email')
                ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('license_plate', 'like', "%{$search}%");
                }))
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Angkutans/Create', [
            'operators' => $this->operators(),
        ]);
    }

    public function store(Request $request)
    {
        Angkutan::create($this->validatedData($request));

        return redirect()->route('angkutans.index')->with('success', 'Data transportasi berhasil ditambahkan.');
    }

    public function show(Angkutan $angkutan)
    {
        return Inertia::render('Angkutans/Show', [
            'angkutan' => $angkutan->load('operator:id,name,email'),
        ]);
    }

    public function edit(Angkutan $angkutan)
    {
        return Inertia::render('Angkutans/Edit', [
            'angkutan' => $angkutan,
            'operators' => $this->operators(),
        ]);
    }

    public function update(Request $request, Angkutan $angkutan)
    {
        $angkutan->update($this->validatedData($request, $angkutan));

        return redirect()->route('angkutans.index')->with('success', 'Data transportasi berhasil diperbarui.');
    }

    public function destroy(Angkutan $angkutan)
    {
        DB::transaction(function () use ($angkutan) {
            $lockedAngkutan = Angkutan::query()->lockForUpdate()->findOrFail($angkutan->id);

            if ($lockedAngkutan->rutes()->exists()
                || $lockedAngkutan->jadwals()->exists()
                || $lockedAngkutan->tarifs()->exists()
                || $lockedAngkutan->verifikasis()->exists()) {
                throw ValidationException::withMessages([
                    'angkutan' => 'Data transportasi tidak dapat dihapus karena masih memiliki rute, jadwal, tarif, atau data verifikasi.',
                ]);
            }

            $lockedAngkutan->delete();
        });

        return redirect()->route('angkutans.index')->with('success', 'Data transportasi berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Angkutan $angkutan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:255', Rule::unique('angkutans', 'kode')->ignore($angkutan?->id)],
            'jenis' => ['required', 'string', 'max:255'],
            'status_operasional' => ['required', Rule::in(['aktif', 'tidak_aktif'])],
            'operator_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'pengemudi')),
            ],
            'vehicle_number' => ['required', 'string', 'max:255', Rule::unique('angkutans', 'vehicle_number')->ignore($angkutan?->id)],
            'license_plate' => ['required', 'string', 'max:255', Rule::unique('angkutans', 'license_plate')->ignore($angkutan?->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ], [
            'operator_id.exists' => 'Operator harus merupakan pengguna dengan role Pengemudi.',
            'capacity.min' => 'Kapasitas harus lebih besar dari nol.',
            'capacity.max' => 'Kapasitas melebihi batas maksimum yang didukung database.',
        ]);
    }

    private function operators()
    {
        return User::query()
            ->where('role', 'pengemudi')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
