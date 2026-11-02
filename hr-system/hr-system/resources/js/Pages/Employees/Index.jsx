import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ employees, departments, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [department, setDepartment] = useState(filters.department ?? '');
    const [rank, setRank] = useState(filters.rank ?? '');
    const sort = filters.sort ?? 'asc';

    // Reload the page with the filters in the query string (?search=...&rank=...)
    const applyFilters = (newSort = sort) => {
        router.get(
            route('employees.index'),
            { search, department, rank, sort: newSort },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (employee) => {
        if (confirm('Delete this employee?')) {
            router.delete(route('employees.destroy', employee.id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Employees</PageHeader>}>
            <Head title="Employees" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Search + filters */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                        className="mb-4 flex flex-col gap-3 px-4 sm:px-0 md:flex-row md:items-center md:justify-between"
                    >
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search name or email..."
                            className="w-full rounded-md border-gray-300 py-2 text-sm shadow-sm md:w-1/3"
                        />

                        <div className="flex items-center gap-2">
                            <SelectInput
                                value={department}
                                onChange={(e) => setDepartment(e.target.value)}
                                className="py-2 text-sm"
                            >
                                <option value="">All Departments</option>
                                {departments.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </SelectInput>

                            <SelectInput
                                value={rank}
                                onChange={(e) => setRank(e.target.value)}
                                className="py-2 text-sm"
                            >
                                <option value="">All Ranks</option>
                                <option value="employee">Employee</option>
                                <option value="head">Head</option>
                            </SelectInput>

                            <button
                                type="button"
                                onClick={() => applyFilters(sort === 'asc' ? 'desc' : 'asc')}
                                className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-100"
                            >
                                {sort === 'asc' ? 'Z–A' : 'A–Z'}
                            </button>

                            <button
                                type="submit"
                                className="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700"
                            >
                                Apply
                            </button>
                        </div>
                    </form>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-6 flex items-center justify-between">
                            <h3 className="text-lg font-medium text-gray-700">Employee List</h3>
                            <Link
                                href={route('employees.create')}
                                className="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                            >
                                + Add Employee
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Email</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Department</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Rank</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {employees.data.map((employee) => (
                                        <tr key={employee.id}>
                                            <td className="px-4 py-3">{employee.user.name}</td>
                                            <td className="px-4 py-3">{employee.user.email}</td>
                                            <td className="px-4 py-3">{employee.department?.name ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                {employee.rank === 'head' ? (
                                                    <span className="rounded bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700">Head</span>
                                                ) : (
                                                    <span className="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Employee</span>
                                                )}
                                            </td>
                                            <td className="space-x-3 px-4 py-3 text-right">
                                                <Link
                                                    href={route('employees.edit', employee.id)}
                                                    className="text-indigo-600 hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => destroy(employee)}
                                                    className="text-red-600 hover:underline"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}

                                    {employees.data.length === 0 && (
                                        <tr>
                                            <td colSpan="5" className="px-4 py-6 text-center text-gray-500">
                                                No employees found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <Pagination links={employees.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
