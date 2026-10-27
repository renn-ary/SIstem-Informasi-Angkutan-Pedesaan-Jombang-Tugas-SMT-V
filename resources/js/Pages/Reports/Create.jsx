import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ routes }) {
    const { data, setData, post, processing, errors } = useForm({
        route_id: '',
        category: '',
        description: '',
        latitude: '',
        longitude: '',
    });

    const submit = event => {
        event.preventDefault();
        post(route('reports.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold">Kirim Laporan Layanan</h2>}
        >
            <Head title="Kirim Laporan Layanan" />
            <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4 p-6">
                <div>
                    <InputLabel htmlFor="route_id" value="Rute (opsional)" />
                    <select
                        id="route_id"
                        className="mt-1 w-full rounded border-gray-300"
                        value={data.route_id}
                        onChange={event => setData('route_id', event.target.value)}
                    >
                        <option value="">Pilih rute</option>
                        {routes.map(item => (
                            <option key={item.id} value={item.id}>
                                {item.asal} → {item.tujuan}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.route_id} />
                </div>

                <div>
                    <InputLabel htmlFor="category" value="Kategori (opsional)" />
                    <TextInput
                        id="category"
                        className="mt-1 w-full"
                        value={data.category}
                        onChange={event => setData('category', event.target.value)}
                    />
                    <InputError message={errors.category} />
                </div>

                <div>
                    <InputLabel htmlFor="description" value="Isi Laporan" />
                    <textarea
                        id="description"
                        className="mt-1 w-full rounded border-gray-300"
                        rows="5"
                        value={data.description}
                        onChange={event => setData('description', event.target.value)}
                        required
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="latitude" value="Latitude (opsional)" />
                        <TextInput
                            id="latitude"
                            type="number"
                            step="any"
                            className="mt-1 w-full"
                            value={data.latitude}
                            onChange={event => setData('latitude', event.target.value)}
                        />
                        <InputError message={errors.latitude} />
                    </div>
                    <div>
                        <InputLabel htmlFor="longitude" value="Longitude (opsional)" />
                        <TextInput
                            id="longitude"
                            type="number"
                            step="any"
                            className="mt-1 w-full"
                            value={data.longitude}
                            onChange={event => setData('longitude', event.target.value)}
                        />
                        <InputError message={errors.longitude} />
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        {processing ? 'Mengirim...' : 'Kirim Laporan'}
                    </PrimaryButton>
                    <Link href={route('reports.index')}>Batal</Link>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
