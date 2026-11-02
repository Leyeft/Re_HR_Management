import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import DepartmentForm from '@/Components/DepartmentForm';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('departments.store'));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Create Department</PageHeader>}>
            <Head title="Create Department" />

            <div className="mx-auto max-w-3xl py-12 sm:px-6 lg:px-8">
                <DepartmentForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Save"
                />
            </div>
        </AuthenticatedLayout>
    );
}
