import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ employee, departments }) {
    const { data, setData, put, processing, errors } = useForm({
        name: employee.user.name,
        email: employee.user.email,
        date_of_birth: employee.date_of_birth ?? '',
        department_id: employee.department_id,
        rank: employee.rank,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Edit Employee</PageHeader>}>
            <Head title="Edit Employee" />

            <div className="mx-auto max-w-4xl py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput id="name" className="mt-1 block w-full" value={data.name}
                            onChange={(e) => setData('name', e.target.value)} required />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email}
                            onChange={(e) => setData('email', e.target.value)} required />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="date_of_birth" value="Date of Birth" />
                        <TextInput id="date_of_birth" type="date" className="mt-1 block w-full" value={data.date_of_birth}
                            onChange={(e) => setData('date_of_birth', e.target.value)} required />
                        <InputError message={errors.date_of_birth} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="department_id" value="Department" />
                        <SelectInput id="department_id" className="mt-1 block w-full" value={data.department_id}
                            onChange={(e) => setData('department_id', e.target.value)}>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </SelectInput>
                        <InputError message={errors.department_id} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="rank" value="Rank" />
                        <SelectInput id="rank" className="mt-1 block w-full" value={data.rank}
                            onChange={(e) => setData('rank', e.target.value)}>
                            <option value="employee">Employee</option>
                            <option value="head">Head of Department</option>
                        </SelectInput>
                        <InputError message={errors.rank} className="mt-1" />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>Update Employee</PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
