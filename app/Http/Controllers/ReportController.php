<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Rute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->string('search')->trim()->value();

        $reports = Report::query()
            ->with(['user:id,name', 'route:id,asal,tujuan'])
            ->when($user->role === 'penumpang', fn (Builder $query) => $query->where('user_id', $user->id))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('description', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'canManage' => $user->role === 'admin',
            'canCreate' => $user->role === 'penumpang',
        ]);
    }

    public function create()
    {
        return Inertia::render('Reports/Create', [
            'routes' => Rute::query()->orderBy('asal')->get(['id', 'asal', 'tujuan']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'route_id' => ['nullable', 'integer', Rule::exists('rutes', 'id')],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'description.required' => 'Isi laporan wajib diisi.',
            'route_id.exists' => 'Rute yang dipilih tidak ditemukan.',
        ]);

        Report::create([
            'user_id' => $request->user()->id,
            'route_id' => $data['route_id'] ?? null,
            'category' => $data['category'] ?? null,
            'description' => $data['description'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'status' => Report::STATUS_MENUNGGU_TINDAK_LANJUT,
        ]);

        return redirect()->route('reports.index')->with('success', 'Laporan berhasil dikirim.');
    }

    public function show(Request $request, Report $report)
    {
        $user = $request->user();

        if ($user->role !== 'admin' && $report->user_id !== $user->id) {
            abort(403);
        }

        return Inertia::render('Reports/Show', [
            'report' => $report->load(['user:id,name', 'route:id,asal,tujuan']),
            'canManage' => $user->role === 'admin',
        ]);
    }

    public function tindakLanjut(Request $request, Report $report)
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Report::STATUS_MENUNGGU_TINDAK_LANJUT,
                Report::STATUS_DITINDAKLANJUTI,
            ])],
            'response' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($report, $data) {
            $locked = Report::query()->lockForUpdate()->findOrFail($report->id);
            $locked->update($data);
        });

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil ditindaklanjuti.');
    }
}
