import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const labelStatus = {
    menunggu_tindak_lanjut: 'Menunggu tindak lanjut',
    ditindaklanjuti: 'Ditindaklanjuti',
};

export default function Show({ report, canManage }) {
    const { flash, errors } = usePage().props;

    const { data, setData, put, processing, errors: formErrors } = useForm({
        status: report.status,
        response: report.response ?? '',
    });

    const submit = event => {
        event.preventDefault();
        put(route('reports.tindak-lanjut', report.id));
    };

    const fields = [
        ['Tanggal', report.created_at ? String(report.created_at).slice(0, 10) : '—'],
        ['Pelapor', report.user?.name || '—'],
        ['Rute', report.route ? `${report.route.asal} → ${report.route.tujuan}` : '—'],
        ['Kategori', report.category || '—'],
        ['Isi Laporan', report.description],
        ['Latitude', report.latitude ?? '—'],
        ['Longitude', report.longitude ?? '—'],
        ['Status', labelStatus[report.status] || report.status],
        ['Tindak Lanjut', report.response || '—'],
    ];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Detail Laporan Layanan</h2>}>
            <Head title="Detail Laporan Layanan" />
            <div className="mx-auto max-w-3xl space-y-4 p-6">
                {flash?.success && (
                    <p role="status" className="rounded bg-green-100 p-3 text-green-800">
                        {flash.success}
                    </p>
                )}
                {errors.report && (
                    <p role="alert" className="rounded bg-red-100 p-3 text-red-800">
                        {errors.report}
                    </p>
                )}

                <dl className="divide-y rounded bg-white p-5 shadow-sm">
                    {fields.map(([label, value]) => (
                        <div key={label} className="grid gap-1 py-3 sm:grid-cols-3">
                            <dt className="font-semibold">{label}</dt>
                            <dd className="whitespace-pre-line sm:col-span-2">{value}</dd>
                        </div>
                    ))}
                </dl>

                {canManage && (
                    <form onSubmit={submit} className="space-y-4 rounded bg-white p-5 shadow-sm">
                        <h3 className="text-lg font-semibold">Tindak Lanjut</h3>
                        <div>
                            <InputLabel htmlFor="status" value="Status Laporan" />
                            <select
                                id="status"
                                className="mt-1 w-full rounded border-gray-300"
                                value={data.status}
                                onChange={event => setData('status', event.target.value)}
                                required
                            >
                                <option value="menunggu_tindak_lanjut">Menunggu tindak lanjut</option>
                                <option value="ditindaklanjuti">Ditindaklanjuti</option>
                            </select>
                            <InputError message={formErrors.status} />
                        </div>
                        <div>
                            <InputLabel htmlFor="response" value="Tanggapan" />
                            <textarea
                                id="response"
                                className="mt-1 w-full rounded border-gray-300"
                                rows="4"
                                value={data.response}
                                onChange={event => setData('response', event.target.value)}
                            />
                            <InputError message={formErrors.response} />
                        </div>
                        <PrimaryButton disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Tindak Lanjut'}
                        </PrimaryButton>
                    </form>
                )}

                <div>
                    <Link className="text-indigo-700" href={route('reports.index')}>
                        Kembali
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
