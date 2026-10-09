import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ users, flash }) {
    const remove = (id) => { if (confirm('Hapus pengguna ini?')) router.delete(route('users.destroy', id)); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Kelola Pengguna</h2>}><Head title="Kelola Pengguna"/><div className="mx-auto max-w-7xl p-6">
        {flash?.success && <p className="mb-4 text-green-700">{flash.success}</p>}
        <Link className="rounded bg-indigo-600 px-4 py-2 text-white" href={route('users.create')}>Tambah Pengguna</Link>
        <div className="mt-5 overflow-x-auto bg-white"><table className="w-full text-left"><thead><tr><th className="p-3">Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            {users.data.map(user => <tr key={user.id} className="border-t"><td className="p-3">{user.name}</td><td>{user.email}</td><td>{user.role}</td><td>{user.status}</td><td className="space-x-3"><Link href={route('users.show', user.id)}>Detail</Link><Link href={route('users.edit', user.id)}>Ubah</Link><button className="text-red-700" onClick={() => remove(user.id)}>Hapus</button></td></tr>)}
        </tbody></table></div>
        <div className="mt-4 flex gap-3">{users.links.map(link => <Link key={link.label} href={link.url ?? '#'} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} className={link.active ? 'font-bold' : ''}/>)}</div>
    </div></AuthenticatedLayout>;
}
