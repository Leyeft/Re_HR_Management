import DangerButton from '@/Components/DangerButton';
import EmployeeDetails from '@/Components/EmployeeDetails';
import ExportButtons from '@/Components/ExportButtons';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const CONFIRM_WORD = 'DELETE';

// Themed blockers shown when deleting would remove the last Admin / HR.
const PROTECTED = {
    admin: {
        icon: '👑',
        title: 'The crown cannot be left upon an empty throne!',
        body: 'Thou wouldst dethrone the last admin of this realm? With no ruler, the castle gates shall lock forever and none shall enter, not even thee. Crown a worthy heir first, lest the realm fall into eternal darkness (and nobody gets paid on Friday).',
        heir: 'Crown an heir',
    },
    hr: {
        icon: '🛡️',
        title: 'A knight never strikes the last healer.',
        body: '’Tis the final HR of the kingdom! Slay them, and who shall tend the wounded spirits of the payroll-stricken? Appoint another healer first.',
        heir: 'Appoint a healer',
    },
    head: {
        icon: '⚔️',
        title: 'A castle without a lord falls by nightfall!',
        body: 'This is the only Head of their department. Strike them down and every leave request in that castle shall wander the halls forever, with no lord to grant it. Crown a new Head for the department first.',
        heir: 'Find a new Head',
    },
};

export default function Index({ employees, departments, filters, roleCounts }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [department, setDepartment] = useState(filters.department ?? '');
    const [rank, setRank] = useState(filters.rank ?? '');
    const sort = filters.sort ?? 'asc';

    // Employee details popup.
    const [viewingEmployee, setViewingEmployee] = useState(null);

    // Type-to-confirm delete: holds the employee being deleted + the typed text.
    const [confirmingEmployee, setConfirmingEmployee] = useState(null);
    const [confirmText, setConfirmText] = useState('');
    const [deleting, setDeleting] = useState(false);

    const closeDeleteModal = () => {
        setConfirmingEmployee(null);
        setConfirmText('');
        setDeleting(false);
    };

    // If deleting this person would remove the last Admin / HR, show a blocker instead.
    const deletingRole = confirmingEmployee?.user?.role;
    // Same for the only Head of a department that still has staff.
    const protectedInfo =
        (deletingRole === 'admin' && (roleCounts?.admin ?? 0) <= 1) ||
        (deletingRole === 'hr' && (roleCounts?.hr ?? 0) <= 1)
            ? PROTECTED[deletingRole]
            : confirmingEmployee?.is_last_head
              ? PROTECTED.head
              : null;
    const heirHref =
        protectedInfo === PROTECTED.head
            ? route('employees.index', { department: confirmingEmployee.department_id })
            : route('employees.create');

    const confirmDelete = () => {
        if (confirmText !== CONFIRM_WORD || !confirmingEmployee) {
            return;
        }
        router.delete(route('employees.destroy', confirmingEmployee.id), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
            onSuccess: closeDeleteModal,
        });
    };

    // Reload the page with the filters in the query string (?search=...&rank=...)
    const applyFilters = (newSort = sort) => {
        router.get(
            route('employees.index'),
            { search, department, rank, sort: newSort },
            { preserveState: true, replace: true },
        );
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
                                className="rounded-md bg-primary px-3 py-2 text-sm text-white hover:bg-primary-hover"
                            >
                                Apply
                            </button>
                        </div>
                    </form>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-6 flex items-center justify-between">
                            <h3 className="text-lg font-medium text-gray-700">Employee List</h3>
                            <div className="flex flex-wrap items-center gap-3">
                                <ExportButtons routeName="reports.employees" params={filters} />
                                <Link
                                    href={route('employees.create')}
                                    className="rounded bg-primary px-4 py-2 text-white hover:bg-primary-hover"
                                >
                                    + Add Employee
                                </Link>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Email</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Department</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Rank</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Leave (left/yr)</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {employees.data.map((employee) => (
                                        <tr key={employee.id}>
                                            <td className="px-4 py-3 font-mono text-sm text-muted">{employee.employee_code}</td>
                                            <td className="px-4 py-3">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewingEmployee(employee)}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    {employee.user.name}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3">{employee.user.email}</td>
                                            <td className="px-4 py-3">{employee.department?.name ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                {employee.rank === 'head' ? (
                                                    <span className="rounded bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700">Head</span>
                                                ) : (
                                                    <span className="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Employee</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {employee.leave_summary ? (
                                                    <span className="text-sm">
                                                        <span className="font-medium text-content">
                                                            {employee.leave_summary.annual.remaining}/{employee.leave_summary.annual.total ?? employee.leave_summary.annual.entitlement}
                                                        </span>
                                                        <span className="ml-1 text-xs text-muted">
                                                            ({employee.leave_summary.tier})
                                                        </span>
                                                    </span>
                                                ) : (
                                                    <span className="text-muted">—</span>
                                                )}
                                            </td>
                                            <td className="space-x-3 px-4 py-3 text-right">
                                                <Link
                                                    href={route('employees.edit', employee.id)}
                                                    className="text-primary hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => setConfirmingEmployee(employee)}
                                                    className="text-danger hover:underline"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}

                                    {employees.data.length === 0 && (
                                        <tr>
                                            <td colSpan="7" className="px-4 py-6 text-center text-gray-500">
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

            <EmployeeDetails
                employee={viewingEmployee}
                show={!!viewingEmployee}
                onClose={() => setViewingEmployee(null)}
            />

            <Modal show={!!confirmingEmployee} onClose={closeDeleteModal} maxWidth="md">
                {protectedInfo ? (
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-content">
                            {protectedInfo.icon} {protectedInfo.title}
                        </h2>

                        <p className="mt-3 text-sm text-muted">{protectedInfo.body}</p>

                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={closeDeleteModal}>
                                Lower my sword
                            </SecondaryButton>
                            <Link
                                href={heirHref}
                                onClick={closeDeleteModal}
                                className="inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                            >
                                {protectedInfo.heir}
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-content">
                            Delete {confirmingEmployee?.user?.name}?
                        </h2>

                        <p className="mt-2 text-sm text-muted">
                            This permanently removes{' '}
                            <span className="font-medium text-content">
                                {confirmingEmployee?.user?.email}
                            </span>{' '}
                            and all of their records, including leave requests. This cannot be undone.
                        </p>

                        <div className="mt-5">
                            <InputLabel
                                htmlFor="confirm_delete"
                                value={`Type ${CONFIRM_WORD} to confirm`}
                            />
                            <TextInput
                                id="confirm_delete"
                                className="mt-1 block w-full"
                                value={confirmText}
                                onChange={(e) => setConfirmText(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') confirmDelete();
                                }}
                                placeholder={CONFIRM_WORD}
                                autoComplete="off"
                                isFocused
                            />
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={closeDeleteModal}>Cancel</SecondaryButton>
                            <DangerButton
                                onClick={confirmDelete}
                                disabled={confirmText !== CONFIRM_WORD || deleting}
                            >
                                Delete Employee
                            </DangerButton>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
