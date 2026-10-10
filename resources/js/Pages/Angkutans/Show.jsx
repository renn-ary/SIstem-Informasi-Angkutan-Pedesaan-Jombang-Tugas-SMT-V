import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ angkutan }) {
    const fields = [
        ['Nama', angkutan.nama],
        ['Kode (legacy)', angkutan.kode],
        ['Nomor Kendaraan', angkutan.vehicle_number || '—'],
        ['Plat Nomor', angkutan.license_plate || '—'],
        ['Jenis Transportasi', angkutan.jenis || '—'],
        ['Kapasitas', angkutan.capacity ?? '—'],
        ['Pengemudi', angkutan.operator?.name || '—'],
        ['Status Operasional', angkutan.status_operasional],
    ];

    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Detail Transportasi</h2>}>
        <Head title="Detail Transportasi" />
        <div className="mx-auto max-w-3xl space-y-4 p-6">
            <dl className="divide-y rounded bg-white p-5 shadow-sm">{fields.map(([label, value]) => <div key={label} className="grid gap-1 py-3 sm:grid-cols-3"><dt className="font-semibold">{label}</dt><dd className="sm:col-span-2">{value}</dd></div>)}</dl>
            <div className="flex gap-4"><Link className="text-indigo-700" href={route('angkutans.edit', angkutan.id)}>Ubah</Link><Link href={route('angkutans.index')}>Kembali</Link></div>
        </div>
    </AuthenticatedLayout>;
}
