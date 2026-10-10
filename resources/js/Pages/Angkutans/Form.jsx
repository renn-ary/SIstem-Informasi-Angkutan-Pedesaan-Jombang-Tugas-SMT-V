import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function AngkutanForm({ angkutan = {}, operators, editing = false }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nama: angkutan.nama ?? '',
        kode: angkutan.kode ?? '',
        vehicle_number: angkutan.vehicle_number ?? '',
        license_plate: angkutan.license_plate ?? '',
        jenis: angkutan.jenis ?? '',
        capacity: angkutan.capacity ?? '',
        operator_id: angkutan.operator_id ?? '',
        status_operasional: angkutan.status_operasional ?? 'aktif',
    });

    const submit = event => {
        event.preventDefault();
        editing ? put(route('angkutans.update', angkutan.id)) : post(route('angkutans.store'));
    };

    const textFields = [
        ['nama', 'Nama', 'text'],
        ['kode', 'Kode (legacy)', 'text'],
        ['vehicle_number', 'Nomor Kendaraan', 'text'],
        ['license_plate', 'Plat Nomor', 'text'],
        ['jenis', 'Jenis Transportasi', 'text'],
        ['capacity', 'Kapasitas', 'number'],
    ];

    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">{editing ? 'Ubah Data Transportasi' : 'Tambah Data Transportasi'}</h2>}>
        <Head title={editing ? 'Ubah Transportasi' : 'Tambah Transportasi'} />
        <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4 p-6">
            {textFields.map(([field, label, type]) => <div key={field}>
                <InputLabel htmlFor={field} value={label} />
                <TextInput id={field} type={type} min={type === 'number' ? 1 : undefined} max={type === 'number' ? 4294967295 : undefined} className="mt-1 w-full" value={data[field]} onChange={event => setData(field, event.target.value)} required />
                <InputError message={errors[field]} />
            </div>)}
            <div><InputLabel htmlFor="operator_id" value="Pengemudi" /><select id="operator_id" className="mt-1 w-full rounded border-gray-300" value={data.operator_id} onChange={event => setData('operator_id', event.target.value)} required><option value="">Pilih Pengemudi</option>{operators.map(operator => <option key={operator.id} value={operator.id}>{operator.name} ({operator.email})</option>)}</select><InputError message={errors.operator_id} /></div>
            <div><InputLabel htmlFor="status_operasional" value="Status Operasional" /><select id="status_operasional" className="mt-1 w-full rounded border-gray-300" value={data.status_operasional} onChange={event => setData('status_operasional', event.target.value)} required><option value="aktif">Aktif</option><option value="tidak_aktif">Tidak aktif</option></select><InputError message={errors.status_operasional} /></div>
            <div className="flex items-center gap-4"><PrimaryButton disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan'}</PrimaryButton><Link href={route('angkutans.index')}>Batal</Link></div>
        </form>
    </AuthenticatedLayout>;
}
