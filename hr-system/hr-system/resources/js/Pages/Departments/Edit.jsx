import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import DepartmentForm from '@/Components/DepartmentForm';

export default function Edit({ department }) {
    const { data, setData, put, processing, errors } = useForm({
        name: department.name,
        description: department.description ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('departments.update', department.id));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Edit Department</PageHeader>}>
            <Head title="Edit Department" />

            <div className="mx-auto max-w-3xl py-12 sm:px-6 lg:px-8">
                <DepartmentForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Update Department"
                />
            </div>
        </AuthenticatedLayout>
    );
}
