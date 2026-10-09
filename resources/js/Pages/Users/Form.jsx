import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function UserForm({ user = {}, roles, editing = false }) {
    const { data, setData, post, put, processing, errors } = useForm({ name: user.name ?? '', email: user.email ?? '', role: user.role ?? 'penumpang', status: user.status ?? 'aktif', password: '', password_confirmation: '' });
    const submit = e => { e.preventDefault(); editing ? put(route('users.update', user.id)) : post(route('users.store')); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">{editing ? 'Ubah Pengguna' : 'Tambah Pengguna'}</h2>}><Head title="Pengguna"/><form onSubmit={submit} className="mx-auto max-w-2xl space-y-4 p-6">
        {['name','email'].map(field => <div key={field}><InputLabel htmlFor={field} value={field === 'name' ? 'Nama' : 'Email'}/><TextInput id={field} className="mt-1 w-full" type={field === 'email' ? 'email' : 'text'} value={data[field]} onChange={e => setData(field,e.target.value)}/><InputError message={errors[field]}/></div>)}
        <div><InputLabel htmlFor="role" value="Role"/><select id="role" className="mt-1 w-full rounded border-gray-300" value={data.role} onChange={e=>setData('role',e.target.value)}>{roles.map(role=><option key={role}>{role}</option>)}</select><InputError message={errors.role}/></div>
        <div><InputLabel htmlFor="status" value="Status"/><select id="status" className="mt-1 w-full rounded border-gray-300" value={data.status} onChange={e=>setData('status',e.target.value)}><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option></select><InputError message={errors.status}/></div>
        {['password','password_confirmation'].map(field=><div key={field}><InputLabel htmlFor={field} value={field === 'password' ? `Password${editing ? ' (kosongkan jika tidak diubah)' : ''}` : 'Konfirmasi password'}/><TextInput id={field} className="mt-1 w-full" type="password" value={data[field]} onChange={e=>setData(field,e.target.value)} required={!editing && field==='password'}/><InputError message={errors[field]}/></div>)}
        <InputError message={errors.user}/><div className="flex items-center gap-4"><PrimaryButton disabled={processing}>Simpan</PrimaryButton><Link href={route('users.index')}>Batal</Link></div>
    </form></AuthenticatedLayout>;
}
