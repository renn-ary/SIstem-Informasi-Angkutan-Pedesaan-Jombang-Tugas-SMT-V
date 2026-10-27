import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

// ── Utilitas ──────────────────────────────────────────────────────────────────

const rupiah = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value || 0));

const formatJam = (value) => (value ? String(value).slice(0, 5) : '—');

// ── Sub-komponen ──────────────────────────────────────────────────────────────

function GlassCard({ children, className = '' }) {
    return (
        <div className={`rounded-2xl border border-white/10 bg-white/5 shadow-xl backdrop-blur-md ${className}`}>
            {children}
        </div>
    );
}

function SectionTitle({ children }) {
    return (
        <h2 className="mb-4 text-sm font-semibold uppercase tracking-widest text-sky-400">
            {children}
        </h2>
    );
}

function InfoRow({ label, value }) {
    return (
        <div className="flex items-center justify-between py-2.5">
            <span className="text-sm text-white/60">{label}</span>
            <span className="font-medium text-white">{value ?? '—'}</span>
        </div>
    );
}

// ── Halaman Utama ─────────────────────────────────────────────────────────────

export default function Pesan({ rute, jadwal, tarif }) {
    const { errors: pageErrors } = usePage().props;

    const { data, setData, post, processing, errors } = useForm({
        rute_id: rute.id,
        jadwal_id: jadwal.id,
        passenger_count: 1,
    });

    const angkutan = rute.angkutan;
    const tarifPerPenumpang = tarif ? Number(tarif.nominal) : 0;
    const totalFare = tarifPerPenumpang * data.passenger_count;

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('pemesanan.store'));
    };

    // URL kembali ke detail perjalanan.
    const urlDetail = route('perjalanan.show', rute.id);

    return (
        <AuthenticatedLayout>
            <Head title="Konfirmasi Pemesanan" />

            <div className="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
                {/* Dekorasi glow */}
                <div aria-hidden className="pointer-events-none fixed inset-0 overflow-hidden">
                    <div className="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-sky-600/20 blur-3xl" />
                    <div className="absolute bottom-0 right-1/4 h-72 w-72 rounded-full bg-indigo-600/20 blur-3xl" />
                </div>

                <div className="relative mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">

                    {/* Tombol kembali */}
                    <Link
                        href={urlDetail}
                        className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-white/60 transition-colors hover:text-white"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Detail Perjalanan
                    </Link>

                    {/* Header halaman */}
                    <div className="mb-8">
                        <div className="mb-2 inline-flex items-center gap-2 rounded-full border border-sky-500/30 bg-sky-500/10 px-4 py-1.5 text-sm font-medium text-sky-300">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            UC-05 Pesan Perjalanan
                        </div>
                        <h1 className="text-2xl font-extrabold text-white sm:text-3xl">
                            Konfirmasi Pemesanan
                        </h1>
                        <p className="mt-1 text-white/60">
                            Periksa detail perjalanan sebelum mengonfirmasi pemesanan.
                        </p>
                    </div>

                    {/* Pesan error global */}
                    {(pageErrors?.perjalanan || errors?.perjalanan) && (
                        <div
                            role="alert"
                            className="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-red-300"
                        >
                            {pageErrors?.perjalanan ?? errors?.perjalanan}
                        </div>
                    )}

                    {/* ── Ringkasan Perjalanan ── */}
                    <GlassCard className="mb-6 p-6">
                        <SectionTitle>Ringkasan Perjalanan</SectionTitle>

                        {/* Rute */}
                        <div className="mb-5 flex items-center gap-4 rounded-xl border border-white/5 bg-white/5 p-4">
                            <div className="flex flex-col items-center gap-1">
                                <div className="h-2.5 w-2.5 rounded-full bg-sky-400" />
                                <div className="h-8 w-px bg-gradient-to-b from-sky-400 to-amber-400" />
                                <div className="h-2.5 w-2.5 rounded-full bg-amber-400" />
                            </div>
                            <div className="flex-1 space-y-2">
                                <div>
                                    <p className="text-xs text-white/40">Asal</p>
                                    <p className="font-semibold text-white">{rute.asal}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-white/40">Tujuan</p>
                                    <p className="font-semibold text-white">{rute.tujuan}</p>
                                </div>
                            </div>
                        </div>

                        <div className="divide-y divide-white/5">
                            <InfoRow label="Angkutan" value={angkutan?.nama} />
                            <InfoRow label="Kode Angkutan" value={angkutan?.kode} />
                            <InfoRow label="Jenis" value={angkutan?.jenis} />
                            <InfoRow
                                label="Hari"
                                value={jadwal.hari}
                            />
                            <InfoRow
                                label="Jam Berangkat"
                                value={formatJam(jadwal.jam_berangkat)}
                            />
                            {jadwal.jam_selesai && (
                                <InfoRow
                                    label="Jam Selesai"
                                    value={formatJam(jadwal.jam_selesai)}
                                />
                            )}
                            {tarif && (
                                <InfoRow
                                    label="Tarif per Penumpang"
                                    value={rupiah(tarif.nominal)}
                                />
                            )}
                        </div>
                    </GlassCard>

                    {/* ── Formulir Pemesanan ── */}
                    <form onSubmit={handleSubmit} aria-label="Formulir konfirmasi pemesanan">
                        <GlassCard className="mb-6 p-6">
                            <SectionTitle>Detail Pemesanan</SectionTitle>

                            {/* Input tersembunyi */}
                            <input type="hidden" name="rute_id" value={data.rute_id} />
                            <input type="hidden" name="jadwal_id" value={data.jadwal_id} />

                            {/* Jumlah Penumpang */}
                            <div className="mb-6">
                                <label
                                    htmlFor="passenger_count"
                                    className="mb-2 block text-sm font-medium text-white/80"
                                >
                                    Jumlah Penumpang
                                </label>
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setData('passenger_count', Math.max(1, data.passenger_count - 1))
                                        }
                                        className="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 transition-colors hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 disabled:opacity-40"
                                        aria-label="Kurangi jumlah penumpang"
                                        disabled={data.passenger_count <= 1}
                                    >
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
                                        </svg>
                                    </button>
                                    <input
                                        id="passenger_count"
                                        type="number"
                                        min="1"
                                        max="10"
                                        value={data.passenger_count}
                                        onChange={(e) =>
                                            setData('passenger_count', Math.max(1, Math.min(10, parseInt(e.target.value) || 1)))
                                        }
                                        className={`w-20 rounded-xl border bg-white/5 py-2 text-center text-white focus:outline-none focus:ring-2 ${
                                            errors.passenger_count
                                                ? 'border-red-500/60 focus:ring-red-500/40'
                                                : 'border-white/10 focus:border-sky-500/50 focus:ring-sky-500/20'
                                        }`}
                                        aria-describedby={errors.passenger_count ? 'error-passenger' : undefined}
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setData('passenger_count', Math.min(10, data.passenger_count + 1))
                                        }
                                        className="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 transition-colors hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 disabled:opacity-40"
                                        aria-label="Tambah jumlah penumpang"
                                        disabled={data.passenger_count >= 10}
                                    >
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                                {errors.passenger_count && (
                                    <p id="error-passenger" role="alert" className="mt-1.5 text-sm text-red-400">
                                        {errors.passenger_count}
                                    </p>
                                )}
                            </div>
                        </GlassCard>

                        {/* ── Ringkasan Biaya ── */}
                        <GlassCard className="mb-6 p-6">
                            <SectionTitle>Ringkasan Biaya</SectionTitle>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between text-sm text-white/70">
                                    <span>
                                        {rupiah(tarifPerPenumpang)} × {data.passenger_count} penumpang
                                    </span>
                                    <span>{rupiah(totalFare)}</span>
                                </div>
                                <div className="border-t border-white/10 pt-3">
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-white">Total</span>
                                        <span className="text-2xl font-bold text-amber-300">
                                            {rupiah(totalFare)}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs text-white/40">
                                        * Total akhir dihitung ulang oleh server saat konfirmasi.
                                    </p>
                                </div>
                            </div>
                        </GlassCard>

                        {/* ── Tombol Konfirmasi ── */}
                        <button
                            type="submit"
                            disabled={processing || !tarif}
                            className="w-full rounded-xl bg-sky-500 py-4 text-base font-bold text-white shadow-lg shadow-sky-500/30 transition-all duration-200 hover:bg-sky-400 hover:shadow-sky-400/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? (
                                <span className="inline-flex items-center gap-2">
                                    <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Memproses...
                                </span>
                            ) : !tarif ? (
                                'Tarif belum tersedia'
                            ) : (
                                'Konfirmasi Pemesanan'
                            )}
                        </button>

                        {!tarif && (
                            <p className="mt-2 text-center text-sm text-amber-400">
                                Tarif untuk perjalanan ini belum tersedia. Pemesanan tidak dapat dilanjutkan.
                            </p>
                        )}
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
