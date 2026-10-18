<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransaksiController extends Controller
{
    /**
     * Metode pembayaran yang tersedia pada rancangan (tanpa payment gateway).
     */
    public const METODE_PEMBAYARAN = [
        'tunai' => 'Tunai',
        'transfer' => 'Transfer Bank',
    ];

    public function index(Request $request)
    {
        $transaksis = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'schedule:id,angkutan_id,hari,jam_berangkat,jam_selesai',
                'schedule.angkutan:id,nama,vehicle_number,license_plate,status_operasional',
            ])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Transaksis/Index', [
            'transaksis' => $transaksis,
        ]);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeOwnership($request, $booking);

        return Inertia::render('Transaksis/Show', [
            'transaksi' => $booking->load([
                'schedule.angkutan.rutes',
                'payments' => fn ($query) => $query->orderByDesc('id'),
            ]),
            'metodePembayaran' => self::METODE_PEMBAYARAN,
        ]);
    }

    public function bayar(Request $request, Booking $booking)
    {
        $this->authorizeOwnership($request, $booking);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(self::METODE_PEMBAYARAN))],
        ], [
            'method.required' => 'Silakan pilih metode pembayaran terlebih dahulu.',
            'method.in' => 'Metode pembayaran tidak dikenali.',
        ]);

        $hasil = DB::transaction(function () use ($booking, $data) {
            $transaksi = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($transaksi->payment_status === Booking::PEMBAYARAN_LUNAS) {
                return 'sudah_lunas';
            }

            if ($transaksi->booking_status === Booking::STATUS_DIBATALKAN) {
                return 'dibatalkan';
            }

            if ($transaksi->booking_status !== Booking::STATUS_MENUNGGU_PEMBAYARAN) {
                return 'tidak_menunggu';
            }

            if (! $this->verifikasiPembayaran($transaksi)) {
                $transaksi->payments()->create([
                    'amount' => $transaksi->total_fare,
                    'method' => $data['method'],
                    'paid_at' => now(),
                    'status' => Payment::STATUS_GAGAL,
                ]);

                $transaksi->update(['payment_status' => Booking::PEMBAYARAN_GAGAL]);

                return 'gagal';
            }

            $transaksi->payments()->create([
                'amount' => $transaksi->total_fare,
                'method' => $data['method'],
                'paid_at' => now(),
                'status' => Payment::STATUS_LUNAS,
            ]);

            $transaksi->update([
                'payment_status' => Booking::PEMBAYARAN_LUNAS,
                'booking_status' => Booking::STATUS_DIKONFIRMASI,
            ]);

            return 'lunas';
        });

        if ($hasil !== 'lunas') {
            throw ValidationException::withMessages([
                'transaksi' => match ($hasil) {
                    'sudah_lunas' => 'Transaksi ini sudah lunas.',
                    'dibatalkan' => 'Transaksi yang dibatalkan tidak dapat dibayar.',
                    'gagal' => 'Pembayaran gagal. Perjalanan sudah tidak tersedia, silakan coba kembali.',
                    default => 'Transaksi tidak dalam status menunggu pembayaran.',
                },
            ]);
        }

        return redirect()->route('transaksis.show', $booking)
            ->with('success', 'Pembayaran berhasil. Status transaksi menjadi Dikonfirmasi.');
    }

    /**
     * Simulasi verifikasi pembayaran internal (bukan payment gateway).
     * Pembayaran dinyatakan valid bila angkutan terkait masih beroperasi.
     */
    private function verifikasiPembayaran(Booking $transaksi): bool
    {
        $transaksi->loadMissing('schedule.angkutan');

        $angkutan = $transaksi->schedule?->angkutan;

        return $angkutan !== null && $angkutan->status_operasional === 'aktif';
    }

    public function batalkan(Request $request, Booking $booking)
    {
        $this->authorizeOwnership($request, $booking);

        $dibatalkan = DB::transaction(function () use ($booking) {
            $transaksi = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $this->pembatalanDiperbolehkan($transaksi)) {
                return false;
            }

            $transaksi->update(['booking_status' => Booking::STATUS_DIBATALKAN]);

            return true;
        });

        if (! $dibatalkan) {
            throw ValidationException::withMessages([
                'transaksi' => 'Transaksi tidak dapat dibatalkan.',
            ]);
        }

        return redirect()->route('transaksis.show', $booking)
            ->with('success', 'Transaksi dibatalkan.');
    }

    /**
     * Ketentuan pembatalan: transaksi yang masih menunggu pembayaran atau yang
     * sudah dikonfirmasi boleh dibatalkan oleh Penumpang pemiliknya.
     */
    private function pembatalanDiperbolehkan(Booking $transaksi): bool
    {
        return in_array($transaksi->booking_status, [
            Booking::STATUS_MENUNGGU_PEMBAYARAN,
            Booking::STATUS_DIKONFIRMASI,
        ], true);
    }

    private function authorizeOwnership(Request $request, Booking $booking): void
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
