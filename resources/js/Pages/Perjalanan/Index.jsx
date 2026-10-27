import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

// ── Utilitas ──────────────────────────────────────────────────────────────────

const rupiah = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value || 0));

const formatJam = (value) => (value ? String(value).slice(0, 5) : '—');

// ── Sub-komponen ──────────────────────────────────────────────────────────────

function BadgeStatus({ status }) {
    const map = {
        aktif: {
            label: 'Aktif',
            cls: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
        },
        tidak_aktif: {
            label: 'Tidak Aktif',
            cls: 'bg-red-500/20 text-red-300 border border-red-500/30',
        },
    };
    const { label, cls } = map[status] ?? {
        label: status,
        cls: 'bg-gray-500/20 text-gray-300 border border-gray-500/30',
    };
    return (
        <span className={`rounded-full px-3 py-0.5 text-xs font-semibold ${cls}`}>
            {label}
        </span>
    );
}

function GlassCard({ children, className = '' }) {
    return (
        <div
            className={`rounded-2xl border border-white/10 bg-white/5 shadow-xl backdrop-blur-md ${className}`}
        >
            {children}
        </div>
    );
}

function KartuHasilPerjalanan({ rute, asal, tujuan }) {
    const angkutan = rute.angkutan;
    const jadwalPertama = angkutan?.jadwals?.[0];
    const tarifPertama = angkutan?.tarifs?.[0];
    const sudahAktif = angkutan?.status_operasional === 'aktif';

    return (
        <GlassCard className="overflow-hidden">
            {/* Header kartu */}
            <div className="flex items-start justify-between gap-4 p-5 pb-4">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-bold text-white">
                            {angkutan?.nama ?? '—'}
                        </h3>
                        <BadgeStatus status={angkutan?.status_operasional} />
                    </div>
                    <p className="mt-0.5 text-sm text-sky-300/80">
                        {angkutan?.kode ?? '—'}
                        {angkutan?.jenis ? ` · ${angkutan.jenis}` : ''}
                    </p>
                </div>
                {tarifPertama && (
                    <div className="shrink-0 text-right">
                        <p className="text-xl font-bold text-amber-300">
                            {rupiah(tarifPertama.nominal)}
                        </p>
                        <p className="text-xs text-white/50">per penumpang</p>
                    </div>
                )}
            </div>

            {/* Rute */}
            <div className="mx-5 mb-4 rounded-xl border border-white/5 bg-white/5 p-4">
                <div className="flex items-center gap-3">
                    <div className="flex flex-col items-center gap-1">
                        <div className="h-2.5 w-2.5 rounded-full bg-sky-400" />
                        <div className="h-6 w-px bg-white/20" />
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
            </div>

            {/* Jadwal singkat */}
            {jadwalPertama && (
                <div className="mx-5 mb-4 flex flex-wrap gap-3 text-sm text-white/70">
                    <span className="flex items-center gap-1.5">
                        <svg className="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {jadwalPertama.hari}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <svg className="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {formatJam(jadwalPertama.jam_berangkat)}
                        {jadwalPertama.jam_selesai
                            ? ` - ${formatJam(jadwalPertama.jam_selesai)}`
                            : ''}
                    </span>
                    {angkutan?.capacity && (
                        <span className="flex items-center gap-1.5">
                            <svg className="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kapasitas {angkutan.capacity} penumpang
                        </span>
                    )}
                </div>
            )}

            {/* Tombol Detail */}
            <div className="border-t border-white/10 px-5 py-4">
                <Link
                    href={route('perjalanan.show', rute.id) + `?asal=${encodeURIComponent(asal)}&tujuan=${encodeURIComponent(tujuan)}`}
                    className={`inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200 ${
                        sudahAktif
                            ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/25 hover:bg-sky-400 hover:shadow-sky-400/30'
                            : 'bg-sky-500/50 text-white/70 shadow-lg shadow-sky-500/10'
                    }`}
                >
                    Lihat Detail
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </GlassCard>
    );
}

// ── Halaman Utama ─────────────────────────────────────────────────────────────

export default function Index({ asal, tujuan, sudahMencari, rutes, errors }) {
    const { auth } = usePage().props;
    const [formAsal, setFormAsal] = useState(asal ?? '');
    const [formTujuan, setFormTujuan] = useState(tujuan ?? '');
    const [loading, setLoading] = useState(false);

    const handleCari = (e) => {
        e.preventDefault();
        setLoading(true);
        router.get(
            route('perjalanan.index'),
            { asal: formAsal, tujuan: formTujuan },
            {
                preserveState: false,
                onFinish: () => setLoading(false),
            },
        );
    };

    const content = (
        <>
            <Head title="Cari Perjalanan" />

            <div className="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
                {/* Dekorasi glow */}
                <div aria-hidden className="pointer-events-none fixed inset-0 overflow-hidden">
                    <div className="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-sky-600/20 blur-3xl" />
                    <div className="absolute bottom-0 right-1/4 h-72 w-72 rounded-full bg-indigo-600/20 blur-3xl" />
                </div>

                <div className="relative mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="mb-3 inline-flex items-center gap-2 rounded-full border border-sky-500/30 bg-sky-500/10 px-4 py-1.5 text-sm font-medium text-sky-300">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Jombang Transit
                        </div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                            Cari Perjalanan Angkutan
                        </h1>
                        <p className="mt-2 text-white/60">
                            Temukan angkutan pedesaan Jombang sesuai rute perjalanan Anda.
                        </p>
                    </div>

                    {/* Formulir Pencarian */}
                    <GlassCard className="mb-8 p-6">
                        <form onSubmit={handleCari} noValidate aria-label="Formulir pencarian perjalanan">
                            <div className="grid gap-4 sm:grid-cols-2">
                                {/* Asal */}
                                <div>
                                    <label htmlFor="asal" className="mb-1.5 block text-sm font-medium text-white/80">
                                        Lokasi Asal
                                    </label>
                                    <div className="relative">
                                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sky-400">
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input
                                            id="asal"
                                            type="text"
                                            value={formAsal}
                                            onChange={(e) => setFormAsal(e.target.value)}
                                            placeholder="Contoh: Jombang"
                                            autoComplete="off"
                                            className={`w-full rounded-xl border bg-white/5 py-3 pl-10 pr-4 text-white placeholder-white/30 transition-colors focus:outline-none focus:ring-2 ${
                                                errors?.asal
                                                    ? 'border-red-500/60 focus:ring-red-500/40'
                                                    : 'border-white/10 focus:border-sky-500/50 focus:ring-sky-500/20'
                                            }`}
                                            aria-describedby={errors?.asal ? 'error-asal' : undefined}
                                        />
                                    </div>
                                    {errors?.asal && (
                                        <p id="error-asal" role="alert" className="mt-1.5 text-sm text-red-400">
                                            {errors.asal}
                                        </p>
                                    )}
                                </div>

                                {/* Tujuan */}
                                <div>
                                    <label htmlFor="tujuan" className="mb-1.5 block text-sm font-medium text-white/80">
                                        Lokasi Tujuan
                                    </label>
                                    <div className="relative">
                                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-amber-400">
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input
                                            id="tujuan"
                                            type="text"
                                            value={formTujuan}
                                            onChange={(e) => setFormTujuan(e.target.value)}
                                            placeholder="Contoh: Mojoagung"
                                            autoComplete="off"
                                            className={`w-full rounded-xl border bg-white/5 py-3 pl-10 pr-4 text-white placeholder-white/30 transition-colors focus:outline-none focus:ring-2 ${
                                                errors?.tujuan
                                                    ? 'border-red-500/60 focus:ring-red-500/40'
                                                    : 'border-white/10 focus:border-sky-500/50 focus:ring-sky-500/20'
                                            }`}
                                            aria-describedby={errors?.tujuan ? 'error-tujuan' : undefined}
                                        />
                                    </div>
                                    {errors?.tujuan && (
                                        <p id="error-tujuan" role="alert" className="mt-1.5 text-sm text-red-400">
                                            {errors.tujuan}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/30 transition-all duration-200 hover:bg-sky-400 hover:shadow-sky-400/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 disabled:opacity-60"
                                >
                                    {loading ? (
                                        <>
                                            <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                            </svg>
                                            Mencari...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            Cari Perjalanan
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </GlassCard>

                    {/* Hasil Pencarian */}
                    {sudahMencari && !errors?.asal && !errors?.tujuan && (
                        <section aria-label="Hasil pencarian perjalanan">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-white">
                                    {rutes && rutes.data.length > 0
                                        ? `${rutes.total} rute ditemukan`
                                        : 'Hasil Pencarian'}
                                </h2>
                                {rutes && rutes.data.length > 0 && (
                                    <p className="text-sm text-white/50">
                                        {rutes.from}–{rutes.to} dari {rutes.total}
                                    </p>
                                )}
                            </div>

                            {(!rutes || rutes.data.length === 0) && (
                                <GlassCard className="py-16 text-center">
                                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-white/10 bg-white/5">
                                        <svg className="h-8 w-8 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p className="text-lg font-semibold text-white/60">
                                        Angkutan dengan rute yang dipilih tidak ditemukan.
                                    </p>
                                    <p className="mt-1 text-sm text-white/40">
                                        Coba gunakan kata kunci yang berbeda.
                                    </p>
                                </GlassCard>
                            )}

                            {rutes && rutes.data.length > 0 && (
                                <>
                                    <div className="space-y-4">
                                        {rutes.data.map((rute) => (
                                            <KartuHasilPerjalanan
                                                key={rute.id}
                                                rute={rute}
                                                asal={asal}
                                                tujuan={tujuan}
                                            />
                                        ))}
                                    </div>

                                    {rutes.links && rutes.links.length > 3 && (
                                        <nav
                                            aria-label="Paginasi hasil pencarian"
                                            className="mt-6 flex flex-wrap justify-center gap-2"
                                        >
                                            {rutes.links.map((link, index) => (
                                                <Link
                                                    key={`${index}-${link.label}`}
                                                    href={link.url ?? '#'}
                                                    preserveState
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                    className={`rounded-lg px-3 py-1.5 text-sm font-medium transition-colors ${
                                                        link.active
                                                            ? 'bg-sky-500 text-white'
                                                            : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10'
                                                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                                />
                                            ))}
                                        </nav>
                                    )}
                                </>
                            )}
                        </section>
                    )}

                    {/* Ajakan login */}
                    {!auth?.user && sudahMencari && rutes && rutes.data.length > 0 && (
                        <GlassCard className="mt-6 p-5 text-center">
                            <p className="text-white/70">
                                Ingin memesan perjalanan?{' '}
                                <Link href={route('login')} className="font-semibold text-sky-400 underline hover:text-sky-300">
                                    Login
                                </Link>{' '}
                                atau{' '}
                                <Link href={route('register')} className="font-semibold text-sky-400 underline hover:text-sky-300">
                                    daftar
                                </Link>{' '}
                                terlebih dahulu.
                            </p>
                        </GlassCard>
                    )}
                </div>
            </div>
        </>
    );

    if (auth?.user) {
        return <AuthenticatedLayout>{content}</AuthenticatedLayout>;
    }

    return content;
}
