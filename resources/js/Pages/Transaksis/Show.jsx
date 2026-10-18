import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const labelPembayaran = {
    menunggu_pembayaran: 'Menunggu pembayaran',
    lunas: 'Lunas',
    gagal: 'Gagal',
};

const labelPemesanan = {
    menunggu_pembayaran: 'Menunggu pembayaran',
    dikonfirmasi: 'Dikonfirmasi',
    dibatalkan: 'Dibatalkan',
};

const rupiah = value =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value || 0));

export default function Show({ transaksi, metodePembayaran }) {
    const { flash, errors } = usePage().props;

    const { data, setData, post, processing, errors: formErrors } = useForm({
        method: '',
    });

    const angkutan = transaksi.schedule?.angkutan;
    const rute = angkutan?.rutes?.[0];

    const bolehBayar =
        transaksi.booking_status === 'menunggu_pembayaran' &&
        transaksi.payment_status !== 'lunas';

    const bolehBatal = ['menunggu_pembayaran', 'dikonfirmasi'].includes(
        transaksi.booking_status,
    );

    const bayar = event => {
        event.preventDefault();
        post(route('transaksis.bayar', transaksi.id));
    };

    const batalkan = () => {
        if (
            window.confirm(
                'Batalkan transaksi ini? Status transaksi akan menjadi Dibatalkan.',
            )
        ) {
            post(route('transaksis.batalkan', transaksi.id));
        }
    };

    const fields = [
        ['Kode Pemesanan', transaksi.booking_code],
        ['Kendaraan', angkutan?.nama || '—'],
        ['Nomor Kendaraan', angkutan?.vehicle_number || '—'],
        ['Plat Nomor', angkutan?.license_plate || '—'],
        ['Rute', rute ? `${rute.asal} → ${rute.tujuan}` : '—'],
        ['Hari', transaksi.schedule?.hari || '—'],
        [
            'Jam Berangkat',
            transaksi.schedule?.jam_berangkat
                ? String(transaksi.schedule.jam_berangkat).slice(0, 5)
                : '—',
        ],
        ['Jumlah Penumpang', transaksi.passenger_count],
        ['Total Tarif', rupiah(transaksi.total_fare)],
        ['Status Pembayaran', labelPembayaran[transaksi.payment_status] || transaksi.payment_status],
        ['Status Pemesanan', labelPemesanan[transaksi.booking_status] || transaksi.booking_status],
        ['Waktu Pemesanan', transaksi.booked_at || '—'],
    ];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Detail Transaksi</h2>}>
            <Head title="Detail Transaksi" />
            <div className="mx-auto max-w-3xl space-y-4 p-6">
                {flash?.success && (
                    <p role="status" className="rounded bg-green-100 p-3 text-green-800">
                        {flash.success}
                    </p>
                )}
                {errors.transaksi && (
                    <p role="alert" className="rounded bg-red-100 p-3 text-red-800">
                        {errors.transaksi}
                    </p>
                )}

                <dl className="divide-y rounded bg-white p-5 shadow-sm">
                    {fields.map(([label, value]) => (
                        <div key={label} className="grid gap-1 py-3 sm:grid-cols-3">
                            <dt className="font-semibold">{label}</dt>
                            <dd className="sm:col-span-2">{value}</dd>
                        </div>
                    ))}
                </dl>

                {bolehBayar ? (
                    <form
                        onSubmit={bayar}
                        className="space-y-4 rounded bg-white p-5 shadow-sm"
                    >
                        <h3 className="text-lg font-semibold">Informasi Pembayaran</h3>
                        <p className="text-sm text-gray-600">
                            Status pembayaran: <strong>Menunggu pembayaran</strong>. Jumlah yang harus
                            dibayar: <strong>{rupiah(transaksi.total_fare)}</strong>.
                        </p>
                        <div>
                            <InputLabel htmlFor="method" value="Metode Pembayaran" />
                            <select
                                id="method"
                                className="mt-1 w-full rounded border-gray-300"
                                value={data.method}
                                onChange={event => setData('method', event.target.value)}
                                required
                            >
                                <option value="">Pilih metode pembayaran</option>
                                {Object.entries(metodePembayaran).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={formErrors.method} />
                        </div>
                        <PrimaryButton disabled={processing}>
                            {processing ? 'Memproses...' : 'Bayar'}
                        </PrimaryButton>
                    </form>
                ) : (
                    <p className="rounded bg-gray-100 p-4 text-sm text-gray-700">
                        {transaksi.booking_status === 'dibatalkan'
                            ? 'Transaksi dibatalkan.'
                            : 'Transaksi ini sudah dikonfirmasi.'}
                    </p>
                )}

                {bolehBatal && (
                    <div className="space-y-3 rounded bg-white p-5 shadow-sm">
                        <h3 className="text-lg font-semibold">Pembatalan Transaksi</h3>
                        <p className="text-sm text-gray-600">
                            Transaksi dapat dibatalkan selama statusnya belum Dibatalkan.
                        </p>
                        <DangerButton type="button" onClick={batalkan} disabled={processing}>
                            Batalkan
                        </DangerButton>
                    </div>
                )}

                <div>
                    <Link className="text-indigo-700" href={route('transaksis.index')}>
                        Kembali
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
