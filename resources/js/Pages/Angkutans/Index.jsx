import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ angkutans }) {
    const { flash, errors } = usePage().props;

    const remove = id => {
        if (window.confirm('Hapus data transportasi ini? Data dengan relasi tidak dapat dihapus.')) {
            router.delete(route('angkutans.destroy', id));
        }
    };

    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Data Transportasi</h2>}>
        <Head title="Data Transportasi" />
        <div className="mx-auto max-w-7xl space-y-4 p-6">
            {flash?.success && <p role="status" className="rounded bg-green-100 p-3 text-green-800">{flash.success}</p>}
            {errors.angkutan && <p role="alert" className="rounded bg-red-100 p-3 text-red-800">{errors.angkutan}</p>}
            <div className="flex justify-end"><Link className="rounded bg-indigo-600 px-4 py-2 text-white" href={route('angkutans.create')}>Tambah Transportasi</Link></div>
            <div className="overflow-x-auto rounded bg-white shadow-sm">
                <table className="w-full text-left text-sm">
                    <thead className="bg-gray-50"><tr>{['Nama', 'Kode', 'Nomor Kendaraan', 'Plat Nomor', 'Jenis', 'Kapasitas', 'Pengemudi', 'Status', 'Aksi'].map(label => <th key={label} className="p-3">{label}</th>)}</tr></thead>
                    <tbody>{angkutans.data.length ? angkutans.data.map(item => <tr key={item.id} className="border-t">
                        <td className="p-3">{item.nama}</td><td>{item.kode}</td><td>{item.vehicle_number || '—'}</td><td>{item.license_plate || '—'}</td><td>{item.jenis || '—'}</td><td>{item.capacity ?? '—'}</td><td>{item.operator?.name || '—'}</td><td>{item.status_operasional}</td>
                        <td className="whitespace-nowrap p-3"><Link className="mr-3 text-indigo-700" href={route('angkutans.show', item.id)}>Detail</Link><Link className="mr-3 text-indigo-700" href={route('angkutans.edit', item.id)}>Ubah</Link><button className="text-red-700" onClick={() => remove(item.id)}>Hapus</button></td>
                    </tr>) : <tr><td className="p-6 text-center text-gray-500" colSpan="9">Belum ada data transportasi.</td></tr>}</tbody>
                </table>
            </div>
            <div className="flex flex-wrap gap-3">{angkutans.links.map((link, index) => <Link key={`${index}-${link.label}`} href={link.url ?? '#'} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} className={`rounded px-2 py-1 ${link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`} />)}</div>
        </div>
    </AuthenticatedLayout>;
}
