<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\Rute;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * UC-05 Pesan Perjalanan.
 *
 * Menangani alur konfirmasi pemesanan dan penyimpanan Booking.
 * Hanya dapat diakses oleh Penumpang yang sudah login (middleware auth + role:penumpang).
 *
 * Alur:
 *   1. Penumpang membuka halaman konfirmasi pemesanan dari UC-04 (show).
 *   2. Sistem menampilkan ringkasan perjalanan dan tarif yang diambil dari database.
 *   3. Penumpang mengonfirmasi dengan menekan tombol Pesan.
 *   4. Server memvalidasi, menghitung total, dan menyimpan Booking.
 *   5. Sistem mengarahkan Penumpang ke halaman transaksi (UC-06).
 */
class PemesananController extends Controller
{
    /**
     * Tampilkan halaman konfirmasi pemesanan (form).
     *
     * Menerima rute_id dan jadwal_id melalui query string sehingga pengguna
     * tidak dapat memanipulasi tarif atau identitas perjalanan dari frontend.
     */
    public function create(Request $request)
    {
        // Validasi keberadaan rute dan jadwal di server.
        $validated = $request->validate([
            'rute_id'   => ['required', 'integer', 'exists:rutes,id'],
            'jadwal_id' => ['required', 'integer', 'exists:jadwals,id'],
        ], [
            'rute_id.required'   => 'Rute perjalanan tidak ditemukan.',
            'rute_id.exists'     => 'Rute perjalanan tidak ditemukan.',
            'jadwal_id.required' => 'Jadwal perjalanan tidak ditemukan.',
            'jadwal_id.exists'   => 'Jadwal perjalanan tidak ditemukan.',
        ]);

        $rute = Rute::with([
            'angkutan:id,nama,kode,jenis,status_operasional,vehicle_number,license_plate,capacity',
            'angkutan.tarifs:id,angkutan_id,nominal,keterangan',
            'titikPemberhentians' => fn ($q) => $q->orderBy('urutan'),
        ])->findOrFail($validated['rute_id']);

        $jadwal = Jadwal::where('id', $validated['jadwal_id'])
            ->where('angkutan_id', $rute->angkutan_id)
            ->firstOrFail();

        // Verifikasi kelayakan perjalanan sebelum menampilkan form konfirmasi.
        if ($rute->angkutan->status_operasional !== 'aktif') {
            return redirect()->back()->withErrors([
                'perjalanan' => 'Angkutan ini sedang tidak beroperasi dan tidak dapat dipesan.',
            ]);
        }

        // Ambil tarif pertama dari angkutan yang terkait.
        $tarif = $rute->angkutan->tarifs->first();

        return Inertia::render('Perjalanan/Pesan', [
            'rute'   => $rute,
            'jadwal' => $jadwal,
            'tarif'  => $tarif,
        ]);
    }

    /**
     * Proses penyimpanan pemesanan (UC-05 alur utama langkah 6–14).
     *
     * Seluruh kalkulasi tarif dan verifikasi perjalanan dilakukan di server.
     * Pengguna tidak dapat mengirim nilai tarif atau total_fare dari frontend.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rute_id'          => ['required', 'integer', 'exists:rutes,id'],
            'jadwal_id'        => ['required', 'integer', 'exists:jadwals,id'],
            'passenger_count'  => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'rute_id.required'          => 'Data perjalanan tidak valid.',
            'rute_id.exists'            => 'Rute perjalanan tidak ditemukan.',
            'jadwal_id.required'        => 'Data jadwal tidak valid.',
            'jadwal_id.exists'          => 'Jadwal perjalanan tidak ditemukan.',
            'passenger_count.required'  => 'Jumlah penumpang harus diisi.',
            'passenger_count.min'       => 'Jumlah penumpang minimal 1.',
            'passenger_count.max'       => 'Jumlah penumpang maksimal 10.',
        ]);

        $rute = Rute::with([
            'angkutan:id,nama,kode,jenis,status_operasional,capacity',
            'angkutan.tarifs:id,angkutan_id,nominal,keterangan',
        ])->findOrFail($validated['rute_id']);

        $jadwal = Jadwal::where('id', $validated['jadwal_id'])
            ->where('angkutan_id', $rute->angkutan_id)
            ->firstOrFail();

        // Verifikasi status operasional angkutan di server.
        if ($rute->angkutan->status_operasional !== 'aktif') {
            throw ValidationException::withMessages([
                'perjalanan' => 'Angkutan ini sedang tidak beroperasi dan tidak dapat dipesan.',
            ]);
        }

        // Hitung tarif dari database, bukan dari input frontend.
        $tarif = $rute->angkutan->tarifs->first();

        if ($tarif === null) {
            throw ValidationException::withMessages([
                'perjalanan' => 'Informasi tarif untuk perjalanan ini belum tersedia.',
            ]);
        }

        $totalFare = $tarif->nominal * $validated['passenger_count'];

        // Simpan pemesanan dalam database transaction agar atomik.
        $booking = DB::transaction(function () use ($request, $jadwal, $validated, $totalFare) {
            return Booking::create([
                'booking_code'    => $this->generateBookingCode(),
                'user_id'         => $request->user()->id,
                'schedule_id'     => $jadwal->id,
                'passenger_count' => $validated['passenger_count'],
                'total_fare'      => $totalFare,
                'booking_status'  => Booking::STATUS_MENUNGGU_PEMBAYARAN,
                'payment_status'  => Booking::PEMBAYARAN_MENUNGGU,
                'booked_at'       => now(),
            ]);
        });

        return redirect()
            ->route('transaksis.show', $booking)
            ->with('success', 'Pemesanan berhasil dibuat. Silakan selesaikan pembayaran.');
    }

    /**
     * Menghasilkan kode pemesanan unik dengan format BK-XXXXXX.
     */
    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
