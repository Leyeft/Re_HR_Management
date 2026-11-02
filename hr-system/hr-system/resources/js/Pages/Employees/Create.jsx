import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create({ departments }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        date_of_birth: '',
        role: 'employee',
        department_id: '',
        rank: 'employee',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('employees.store'));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Create Employee</PageHeader>}>
            <Head title="Create Employee" />

            <div className="mx-auto max-w-4xl py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel htmlFor="name" value="Full Name" />
                        <TextInput id="name" className="mt-1 block w-full" value={data.name}
                            onChange={(e) => setData('name', e.target.value)} required isFocused />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email}
                            onChange={(e) => setData('email', e.target.value)} required />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="password" value="Password" />
                        <TextInput id="password" type="password" className="mt-1 block w-full" value={data.password}
                            onChange={(e) => setData('password', e.target.value)} required />
                        <InputError message={errors.password} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="date_of_birth" value="Date of Birth" />
                        <TextInput id="date_of_birth" type="date" className="mt-1 block w-full" value={data.date_of_birth}
                            onChange={(e) => setData('date_of_birth', e.target.value)} required />
                        <InputError message={errors.date_of_birth} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="role" value="System Role" />
                        <SelectInput id="role" className="mt-1 block w-full" value={data.role}
                            onChange={(e) => setData('role', e.target.value)} required>
                            <option value="employee">Employee</option>
                            <option value="hr">HR</option>
                            <option value="admin">Admin</option>
                        </SelectInput>
                        <InputError message={errors.role} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="department_id" value="Department" />
                        <SelectInput id="department_id" className="mt-1 block w-full" value={data.department_id}
                            onChange={(e) => setData('department_id', e.target.value)} required>
                            <option value="">Select Department</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </SelectInput>
                        <InputError message={errors.department_id} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="rank" value="Department Rank" />
                        <SelectInput id="rank" className="mt-1 block w-full" value={data.rank}
                            onChange={(e) => setData('rank', e.target.value)} required>
                            <option value="employee">Employee</option>
                            <option value="head">Head of Department</option>
                        </SelectInput>
                        <InputError message={errors.rank} className="mt-1" />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>Create Employee</PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
