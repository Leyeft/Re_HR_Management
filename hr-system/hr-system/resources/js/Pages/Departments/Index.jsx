import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import PageHeader from '@/Components/PageHeader';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ departments }) {
    const [target, setTarget] = useState(null);
    const hasEmployees = (target?.employees_count ?? 0) > 0;

    const confirmDelete = () => {
        router.delete(route('departments.destroy', target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Departments List</PageHeader>}>
            <Head title="Departments" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 text-gray-900 shadow-sm sm:rounded-lg">
                        <div className="mb-4 flex justify-end">
                            <Link
                                href={route('departments.create')}
                                className="rounded bg-primary px-4 py-2 font-bold text-white hover:bg-primary-hover"
                            >
                                Add New Department
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employees</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {departments.map((department) => (
                                        <tr key={department.id}>
                                            <td className="whitespace-nowrap px-6 py-4">{department.name}</td>
                                            <td className="px-6 py-4">{department.description}</td>
                                            <td className="px-6 py-4">{department.employees_count ?? 0}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                <Link
                                                    href={route('departments.edit', department.id)}
                                                    className="mr-4 text-primary hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => setTarget(department)}
                                                    className="text-danger hover:underline"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}

                                    {departments.length === 0 && (
                                        <tr>
                                            <td colSpan="4" className="px-6 py-6 text-center text-gray-500">
                                                No departments yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={!!target} onClose={() => setTarget(null)} maxWidth="md">
                {hasEmployees ? (
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-content">
                            🏰 Halt! The castle still has residents.
                        </h2>
                        <p className="mt-3 text-sm text-muted">
                            Thou wouldst demolish <span className="font-medium text-content">{target?.name}</span> with{' '}
                            {target?.employees_count} employee{target?.employees_count === 1 ? '' : 's'} still inside?
                            Even the cruelest king evacuates first. Move them elsewhere, then return with thy wrecking ram.
                        </p>
                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={() => setTarget(null)}>Lower my sword</SecondaryButton>
                            <Link
                                href={route('employees.index', { department: target?.id })}
                                className="inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                            >
                                Evacuate the castle
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-content">
                            Delete {target?.name}?
                        </h2>
                        <p className="mt-2 text-sm text-muted">
                            This department has no employees. This cannot be undone.
                        </p>
                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={() => setTarget(null)}>Cancel</SecondaryButton>
                            <DangerButton onClick={confirmDelete}>Delete Department</DangerButton>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
