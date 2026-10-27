import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

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

export default function Index({ transaksis }) {
    const { flash } = usePage().props;

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold">Transaksi Saya</h2>}
        >
            <Head title="Transaksi Saya" />
            <div className="mx-auto max-w-7xl space-y-4 p-6">
                {flash?.success && (
                    <p role="status" className="rounded bg-green-100 p-3 text-green-800">
                        {flash.success}
                    </p>
                )}
                <div className="overflow-x-auto rounded bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                {[
                                    'Kode Pemesanan',
                                    'Kendaraan',
                                    'Hari',
                                    'Jam Berangkat',
                                    'Penumpang',
                                    'Total Tarif',
                                    'Status Pembayaran',
                                    'Status Pemesanan',
                                    'Aksi',
                                ].map(label => (
                                    <th key={label} className="p-3">
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {transaksis.data.length ? (
                                transaksis.data.map(item => (
                                    <tr key={item.id} className="border-t">
                                        <td className="p-3 font-medium">{item.booking_code}</td>
                                        <td className="p-3">
                                            {item.schedule?.angkutan?.nama || '—'}
                                        </td>
                                        <td className="p-3">{item.schedule?.hari || '—'}</td>
                                        <td className="p-3">
                                            {item.schedule?.jam_berangkat
                                                ? String(item.schedule.jam_berangkat).slice(0, 5)
                                                : '—'}
                                        </td>
                                        <td className="p-3">{item.passenger_count}</td>
                                        <td className="p-3">{rupiah(item.total_fare)}</td>
                                        <td className="p-3">
                                            {labelPembayaran[item.payment_status] || item.payment_status}
                                        </td>
                                        <td className="p-3">
                                            {labelPemesanan[item.booking_status] || item.booking_status}
                                        </td>
                                        <td className="whitespace-nowrap p-3">
                                            <Link
                                                className="text-indigo-700"
                                                href={route('transaksis.show', item.id)}
                                            >
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td className="p-6 text-center text-gray-500" colSpan="9">
                                        Belum terdapat transaksi.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="flex flex-wrap gap-3">
                    {transaksis.links.map((link, index) => (
                        <Link
                            key={`${index}-${link.label}`}
                            href={link.url ?? '#'}
                            preserveScroll
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`rounded px-2 py-1 ${
                                link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'
                            } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
