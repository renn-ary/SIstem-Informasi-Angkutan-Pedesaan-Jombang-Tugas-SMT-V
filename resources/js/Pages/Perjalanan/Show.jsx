import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

// ── Utilitas ──────────────────────────────────────────────────────────────────

const rupiah = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value || 0));

const formatJam = (value) => (value ? String(value).slice(0, 5) : '—');

const HARI_ORDER = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

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

function BadgeStatus({ status }) {
    const map = {
        aktif: { label: 'Aktif', cls: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' },
        tidak_aktif: { label: 'Tidak Aktif', cls: 'bg-red-500/20 text-red-300 border border-red-500/30' },
    };
    const { label, cls } = map[status] ?? { label: status, cls: 'bg-gray-500/20 text-gray-300 border border-gray-500/30' };
    return (
        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${cls}`}>
            {label}
        </span>
    );
}

// ── Halaman Utama ─────────────────────────────────────────────────────────────

export default function Show({ rute, querySebelumnya }) {
    const { auth } = usePage().props;
    const angkutan = rute.angkutan;
    const sudahAktif = angkutan?.status_operasional === 'aktif';

    // URL kembali ke halaman pencarian dengan parameter sebelumnya.
    const urlKembali = (() => {
        const params = new URLSearchParams();
        if (querySebelumnya?.asal) params.set('asal', querySebelumnya.asal);
        if (querySebelumnya?.tujuan) params.set('tujuan', querySebelumnya.tujuan);
        const qs = params.toString();
        return route('perjalanan.index') + (qs ? `?${qs}` : '');
    })();

    // Urutkan jadwal berdasarkan hari.
    const jadwalsSorted = [...(angkutan?.jadwals ?? [])].sort(
        (a, b) => HARI_ORDER.indexOf(a.hari) - HARI_ORDER.indexOf(b.hari),
    );

    // Titik pemberhentian sudah diurutkan oleh server.
    const titikPemberhentians = rute.titik_pemberhentians ?? [];

    const content = (
        <>
            <Head title={`Detail Perjalanan — ${rute.asal} ke ${rute.tujuan}`} />

            <div className="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
                {/* Dekorasi */}
                <div aria-hidden className="pointer-events-none fixed inset-0 overflow-hidden">
                    <div className="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-sky-600/20 blur-3xl" />
                    <div className="absolute bottom-0 right-1/4 h-72 w-72 rounded-full bg-indigo-600/20 blur-3xl" />
                </div>

                <div className="relative mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

                    {/* Tombol kembali */}
                    <Link
                        href={urlKembali}
                        className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-white/60 transition-colors hover:text-white"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Hasil Pencarian
                    </Link>

                    {/* ── Header perjalanan ── */}
                    <GlassCard className="mb-6 p-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <h1 className="text-2xl font-extrabold text-white sm:text-3xl">
                                        {angkutan?.nama ?? '—'}
                                    </h1>
                                    <BadgeStatus status={angkutan?.status_operasional} />
                                </div>
                                <p className="mt-1 text-sky-300/80">
                                    {angkutan?.kode ?? '—'}
                                    {angkutan?.jenis ? ` · ${angkutan.jenis}` : ''}
                                </p>
                            </div>
                            {/* Tarif utama */}
                            {angkutan?.tarifs?.[0] && (
                                <div className="rounded-xl border border-amber-500/20 bg-amber-500/10 px-5 py-3 text-center">
                                    <p className="text-2xl font-bold text-amber-300">
                                        {rupiah(angkutan.tarifs[0].nominal)}
                                    </p>
                                    <p className="text-xs text-white/50">per penumpang</p>
                                </div>
                            )}
                        </div>

                        {/* Rute asal → tujuan */}
                        <div className="mt-5 flex items-center gap-4 rounded-xl border border-white/5 bg-white/5 p-4">
                            <div className="flex flex-col items-center gap-1">
                                <div className="h-3 w-3 rounded-full bg-sky-400" />
                                <div className="h-10 w-px bg-gradient-to-b from-sky-400 to-amber-400" />
                                <div className="h-3 w-3 rounded-full bg-amber-400" />
                            </div>
                            <div className="flex-1 space-y-3">
                                <div>
                                    <p className="text-xs text-white/40">Asal</p>
                                    <p className="text-lg font-bold text-white">{rute.asal}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-white/40">Tujuan</p>
                                    <p className="text-lg font-bold text-white">{rute.tujuan}</p>
                                </div>
                            </div>
                        </div>

                        {/* Jalur */}
                        {rute.jalur && (
                            <p className="mt-4 text-sm text-white/60">
                                <span className="font-medium text-white/80">Jalur: </span>
                                {rute.jalur}
                            </p>
                        )}
                    </GlassCard>

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* ── Titik Pemberhentian ── */}
                        <GlassCard className="p-6">
                            <SectionTitle>Titik Pemberhentian</SectionTitle>
                            {titikPemberhentians.length > 0 ? (
                                <ol className="space-y-2">
                                    {titikPemberhentians.map((titik, idx) => (
                                        <li key={titik.id} className="flex items-center gap-3">
                                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 text-xs font-bold text-sky-300">
                                                {idx + 1}
                                            </span>
                                            <span className="text-white/80">{titik.nama}</span>
                                        </li>
                                    ))}
                                </ol>
                            ) : (
                                <p className="text-sm text-white/40">
                                    Informasi titik pemberhentian belum tersedia.
                                </p>
                            )}
                        </GlassCard>

                        {/* ── Jadwal ── */}
                        <GlassCard className="p-6">
                            <SectionTitle>Jadwal Operasional</SectionTitle>
                            {jadwalsSorted.length > 0 ? (
                                <div className="space-y-2">
                                    {jadwalsSorted.map((jadwal) => (
                                        <div
                                            key={jadwal.id}
                                            className="flex items-center justify-between rounded-xl border border-white/5 bg-white/5 px-4 py-3"
                                        >
                                            <span className="font-medium text-white">{jadwal.hari}</span>
                                            <span className="text-sm text-white/70">
                                                {formatJam(jadwal.jam_berangkat)}
                                                {jadwal.jam_selesai
                                                    ? ` – ${formatJam(jadwal.jam_selesai)}`
                                                    : ''}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-white/40">
                                    Informasi jadwal belum tersedia.
                                </p>
                            )}
                        </GlassCard>

                        {/* ── Tarif ── */}
                        {angkutan?.tarifs && angkutan.tarifs.length > 0 && (
                            <GlassCard className="p-6">
                                <SectionTitle>Tarif</SectionTitle>
                                <div className="space-y-3">
                                    {angkutan.tarifs.map((tarif) => (
                                        <div key={tarif.id} className="flex items-center justify-between">
                                            <p className="text-sm text-white/70">
                                                {tarif.keterangan ?? 'Tarif reguler'}
                                            </p>
                                            <p className="font-bold text-amber-300">
                                                {rupiah(tarif.nominal)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </GlassCard>
                        )}

                        {/* ── Info Kendaraan ── */}
                        {angkutan && (
                            <GlassCard className="p-6">
                                <SectionTitle>Informasi Kendaraan</SectionTitle>
                                <dl className="space-y-2 text-sm">
                                    {[
                                        ['Nomor Kendaraan', angkutan.vehicle_number],
                                        ['Plat Nomor', angkutan.license_plate],
                                        ['Kapasitas', angkutan.capacity ? `${angkutan.capacity} penumpang` : null],
                                    ]
                                        .filter(([, val]) => val)
                                        .map(([label, val]) => (
                                            <div key={label} className="flex justify-between">
                                                <dt className="text-white/50">{label}</dt>
                                                <dd className="font-medium text-white">{val}</dd>
                                            </div>
                                        ))}
                                </dl>
                            </GlassCard>
                        )}
                    </div>

                    {/* ── Tombol Pesan Perjalanan ── */}
                    <div className="mt-8">
                        {!auth?.user ? (
                            /* Belum login */
                            <GlassCard className="p-6 text-center">
                                <p className="mb-3 text-white/70">
                                    Anda harus login sebagai Penumpang untuk memesan perjalanan.
                                </p>
                                <Link
                                    href={route('login')}
                                    className="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/30 transition-all hover:bg-sky-400"
                                >
                                    Login untuk Memesan
                                </Link>
                            </GlassCard>
                        ) : auth.user.role !== 'penumpang' ? (
                            /* Login tapi bukan penumpang */
                            null
                        ) : !sudahAktif ? (
                            /* Angkutan tidak aktif */
                            <GlassCard className="border-red-500/20 bg-red-500/5 p-6 text-center">
                                <p className="text-red-300">
                                    Angkutan ini sedang tidak beroperasi dan tidak dapat dipesan.
                                </p>
                            </GlassCard>
                        ) : jadwalsSorted.length === 0 ? (
                            /* Tidak ada jadwal */
                            <GlassCard className="border-amber-500/20 bg-amber-500/5 p-6 text-center">
                                <p className="text-amber-300">
                                    Belum ada jadwal yang tersedia untuk rute ini.
                                </p>
                            </GlassCard>
                        ) : (
                            /* Dapat dipesan — tampilkan pilihan jadwal */
                            <GlassCard className="p-6">
                                <h2 className="mb-4 text-lg font-bold text-white">
                                    Pesan Perjalanan
                                </h2>
                                <p className="mb-5 text-sm text-white/60">
                                    Pilih jadwal keberangkatan untuk melanjutkan pemesanan.
                                </p>
                                <div className="space-y-3">
                                    {jadwalsSorted.map((jadwal) => (
                                        <Link
                                            key={jadwal.id}
                                            href={`${route('pemesanan.create')}?rute_id=${rute.id}&jadwal_id=${jadwal.id}`}
                                            className="flex items-center justify-between rounded-xl border border-sky-500/20 bg-sky-500/10 px-5 py-4 transition-all hover:border-sky-400/40 hover:bg-sky-500/20"
                                        >
                                            <span className="font-semibold text-white">
                                                {jadwal.hari}
                                            </span>
                                            <span className="flex items-center gap-4 text-sm text-white/70">
                                                <span>
                                                    {formatJam(jadwal.jam_berangkat)}
                                                    {jadwal.jam_selesai ? ` – ${formatJam(jadwal.jam_selesai)}` : ''}
                                                </span>
                                                <svg className="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                </svg>
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            </GlassCard>
                        )}
                    </div>
                </div>
            </div>
        </>
    );

    if (auth?.user) {
        return <AuthenticatedLayout>{content}</AuthenticatedLayout>;
    }
    return content;
}
