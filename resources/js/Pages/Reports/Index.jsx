import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const labelStatus = {
    menunggu_tindak_lanjut: 'Menunggu tindak lanjut',
    ditindaklanjuti: 'Ditindaklanjuti',
};

export default function Index({ reports, canManage, canCreate }) {
    const { flash } = usePage().props;

    const columns = [
        'Tanggal',
        ...(canManage ? ['Pelapor'] : []),
        'Rute',
        'Kategori',
        'Isi Laporan',
        'Status',
        'Aksi',
    ];

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold">Laporan Layanan</h2>}
        >
            <Head title="Laporan Layanan" />
            <div className="mx-auto max-w-7xl space-y-4 p-6">
                {flash?.success && (
                    <p role="status" className="rounded bg-green-100 p-3 text-green-800">
                        {flash.success}
                    </p>
                )}
                {canCreate && (
                    <div className="flex justify-end">
                        <Link
                            className="rounded bg-indigo-600 px-4 py-2 text-white"
                            href={route('reports.create')}
                        >
                            Kirim Laporan
                        </Link>
                    </div>
                )}
                <div className="overflow-x-auto rounded bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                {columns.map(label => (
                                    <th key={label} className="p-3">
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {reports.data.length ? (
                                reports.data.map(item => (
                                    <tr key={item.id} className="border-t">
                                        <td className="p-3">
                                            {item.created_at
                                                ? String(item.created_at).slice(0, 10)
                                                : '—'}
                                        </td>
                                        {canManage && <td className="p-3">{item.user?.name || '—'}</td>}
                                        <td className="p-3">
                                            {item.route
                                                ? `${item.route.asal} → ${item.route.tujuan}`
                                                : '—'}
                                        </td>
                                        <td className="p-3">{item.category || '—'}</td>
                                        <td className="p-3">
                                            {item.description?.length > 60
                                                ? `${item.description.slice(0, 60)}…`
                                                : item.description}
                                        </td>
                                        <td className="p-3">
                                            {labelStatus[item.status] || item.status}
                                        </td>
                                        <td className="whitespace-nowrap p-3">
                                            <Link
                                                className="text-indigo-700"
                                                href={route('reports.show', item.id)}
                                            >
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        className="p-6 text-center text-gray-500"
                                        colSpan={columns.length}
                                    >
                                        Belum ada laporan layanan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="flex flex-wrap gap-3">
                    {reports.links.map((link, index) => (
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
