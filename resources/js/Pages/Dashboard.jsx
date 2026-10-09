import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ dashboardRole }) {
    const user = usePage().props.auth.user;
    const labels = { penumpang: 'Dashboard Penumpang', pengemudi: 'Dashboard Pengemudi', admin: 'Dashboard Admin', 'super-admin': 'Dashboard Super Admin' };
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-semibold">{labels[dashboardRole] ?? 'Dashboard ROMBONGIN'}</h3>
                            <p className="mt-2">Selamat datang, {user.name}. Role: {user.role}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
