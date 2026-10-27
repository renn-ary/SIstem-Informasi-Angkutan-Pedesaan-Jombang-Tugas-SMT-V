<?php

namespace App\Http\Controllers;

use App\Models\Rute;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * UC-03 Cari Perjalanan & UC-04 Lihat Detail Perjalanan.
 *
 * Menyediakan pencarian rute angkutan pedesaan berdasarkan asal dan tujuan
 * serta menampilkan detail perjalanan yang mencakup titik pemberhentian,
 * jadwal, dan tarif angkutan.
 *
 * Akses: publik (tidak memerlukan autentikasi) sesuai Activity Diagram UC-03.
 * Penumpang yang belum login tetap dapat melihat hasil pencarian dan detail,
 * tetapi tombol Pesan Perjalanan hanya ditampilkan bagi yang sudah login.
 */
class PerjalananController extends Controller
{
    /**
     * UC-03: Tampilkan formulir pencarian dan hasil pencarian perjalanan.
     *
     * Mencari rute berdasarkan asal dan tujuan menggunakan pencarian
     * case-insensitive (LIKE). Jika parameter tidak disertakan, halaman
     * ditampilkan tanpa hasil pencarian.
     */
    public function index(Request $request)
    {
        $asal   = $request->string('asal')->trim()->value();
        $tujuan = $request->string('tujuan')->trim()->value();

        // Validasi hanya dijalankan jika pengguna benar-benar mengirim pencarian.
        $sudahMencari = $request->hasAny(['asal', 'tujuan']);
        $errors       = [];

        if ($sudahMencari) {
            if ($asal === '') {
                $errors['asal'] = 'Lokasi asal harus diisi.';
            }
            if ($tujuan === '') {
                $errors['tujuan'] = 'Lokasi tujuan harus diisi.';
            }
        }

        $rutes = null;

        if ($sudahMencari && empty($errors)) {
            $rutes = Rute::query()
                ->where('asal', 'like', "%{$asal}%")
                ->where('tujuan', 'like', "%{$tujuan}%")
                ->with([
                    // Eager-load angkutan beserta jadwal dan tarif.
                    'angkutan:id,nama,kode,jenis,status_operasional,vehicle_number,license_plate,capacity',
                    'angkutan.jadwals:id,angkutan_id,hari,jam_berangkat,jam_selesai',
                    'angkutan.tarifs:id,angkutan_id,nominal,keterangan',
                    // Titik pemberhentian diurutkan berdasarkan kolom urutan.
                    'titikPemberhentians' => fn ($q) => $q->orderBy('urutan'),
                ])
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString();
        }

        return Inertia::render('Perjalanan/Index', [
            'asal'         => $asal,
            'tujuan'       => $tujuan,
            'sudahMencari' => $sudahMencari,
            'rutes'        => $rutes,
            'errors'       => $errors,
        ]);
    }

    /**
     * UC-04: Tampilkan detail perjalanan berdasarkan ID rute.
     *
     * Memuat semua data yang relevan: angkutan, jadwal, tarif, titik
     * pemberhentian. Validasi dilakukan di server; jika data tidak ditemukan,
     * Laravel mengembalikan 404 secara otomatis (findOrFail / route model binding).
     */
    public function show(Request $request, Rute $rute)
    {
        $rute->load([
            'angkutan:id,nama,kode,jenis,status_operasional,vehicle_number,license_plate,capacity',
            'angkutan.jadwals:id,angkutan_id,hari,jam_berangkat,jam_selesai',
            'angkutan.tarifs:id,angkutan_id,nominal,keterangan',
            'titikPemberhentians' => fn ($q) => $q->orderBy('urutan'),
        ]);

        // Parameter pencarian dikembalikan agar tombol "Kembali" membawa pengguna
        // ke halaman hasil pencarian yang sama.
        $querySebelumnya = [
            'asal'   => $request->string('asal')->trim()->value(),
            'tujuan' => $request->string('tujuan')->trim()->value(),
        ];

        return Inertia::render('Perjalanan/Show', [
            'rute'            => $rute,
            'querySebelumnya' => $querySebelumnya,
        ]);
    }
}
