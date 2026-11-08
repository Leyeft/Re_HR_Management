import EmployeeDetails from '@/Components/EmployeeDetails';
import ExportButtons from '@/Components/ExportButtons';
import InputError from '@/Components/InputError';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

// One row. Requests waiting on HR get Approve/Reject controls; others just show status.
function LeaveRow({ leave, onView }) {
    const { data, setData, post, processing, errors } = useForm({
        rejection_reason: '',
    });

    const awaitingHr = leave.status === 'pending_hr';

    const approve = () => {
        router.post(route('hr.leave.approve', leave.id), {}, { preserveScroll: true });
    };

    const reject = (e) => {
        e.preventDefault();
        post(route('hr.leave.reject', leave.id), { preserveScroll: true });
    };

    return (
        <tr className="border-t align-top">
            <td className="p-3">
                <button
                    type="button"
                    onClick={() => onView(leave.employee)}
                    className="font-medium text-primary hover:underline"
                >
                    {leave.employee?.user?.name}
                </button>
            </td>
            <td className="p-3">{leave.employee?.department?.name ?? '—'}</td>
            <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
            <td className="p-3">{leave.start_date} → {leave.end_date}</td>
            <td className="p-3"><LeaveStatus leave={leave} /></td>
            <td className="p-3 text-right">
                {awaitingHr ? (
                    <div className="flex items-start justify-end gap-2">
                        <button
                            type="button"
                            onClick={approve}
                            className="rounded bg-success px-3 py-1 text-sm text-white hover:opacity-90"
                        >
                            Approve
                        </button>

                        <form onSubmit={reject} className="flex flex-col items-end">
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    value={data.rejection_reason}
                                    onChange={(e) => setData('rejection_reason', e.target.value)}
                                    placeholder="Reject reason"
                                    required
                                    className="rounded border-gray-300 px-2 py-1 text-sm"
                                />
                                <button
                                    disabled={processing}
                                    className="rounded bg-danger px-3 py-1 text-sm text-white hover:opacity-90"
                                >
                                    Reject
                                </button>
                            </div>
                            <InputError message={errors.rejection_reason} className="mt-1" />
                        </form>
                    </div>
                ) : (
                    <span className="text-sm text-muted">—</span>
                )}
            </td>
        </tr>
    );
}

const EMPTY_FILTERS = { search: '', department: '', status: '', type: '', from: '', to: '' };

// Search + filters. Applying reloads the page with them in the URL; exports reuse them.
function Filters({ filters, departments }) {
    const [values, setValues] = useState({ ...EMPTY_FILTERS, ...filters });
    const set = (key) => (e) => setValues((v) => ({ ...v, [key]: e.target.value }));

    const apply = (next) =>
        router.get(route('hr.leave.index'), next, { preserveState: true, preserveScroll: true, replace: true });

    const field = 'mt-1 block w-full py-1.5 text-sm';
    const label = 'block text-xs font-medium text-muted';

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                apply(values);
            }}
            className="mb-4 grid gap-3 rounded-lg bg-white p-4 shadow sm:grid-cols-2 lg:grid-cols-6"
        >
            <label className={`${label} lg:col-span-2`}>
                Employee
                <TextInput className={field} value={values.search} onChange={set('search')} placeholder="Name or email" />
            </label>
            <label className={label}>
                Department
                <SelectInput className={field} value={values.department} onChange={set('department')}>
                    <option value="">All</option>
                    {departments.map((d) => (
                        <option key={d.id} value={d.id}>{d.name}</option>
                    ))}
                </SelectInput>
            </label>
            <label className={label}>
                Status
                <SelectInput className={field} value={values.status} onChange={set('status')}>
                    <option value="">All</option>
                    <option value="pending_head">Pending Head</option>
                    <option value="pending_hr">Pending HR</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </SelectInput>
            </label>
            <label className={label}>
                Type
                <SelectInput className={field} value={values.type} onChange={set('type')}>
                    <option value="">All</option>
                    <option value="annual">Annual</option>
                    <option value="sick">Sick</option>
                    <option value="professional">Professional</option>
                </SelectInput>
            </label>
            <div className="hidden lg:block" />
            <label className={label}>
                From
                <TextInput type="date" className={field} value={values.from} onChange={set('from')} />
            </label>
            <label className={label}>
                To
                <TextInput type="date" className={field} value={values.to} min={values.from || undefined}
                    onChange={set('to')} />
            </label>
            <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">
                    Apply
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setValues(EMPTY_FILTERS);
                        apply({});
                    }}
                    className="rounded-md border border-line bg-white px-4 py-2 text-sm hover:bg-bg"
                >
                    Reset
                </button>
            </div>
        </form>
    );
}

export default function Index({ leaves, filters, departments }) {
    const [viewing, setViewing] = useState(null);

    return (
        <AuthenticatedLayout header={<PageHeader>All Leave Requests</PageHeader>}>
            <Head title="All Leave Requests" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p className="mb-4 rounded-lg bg-info-bg p-3 text-sm text-info">
                    You give the final sign-off after a Department Head approves. Requests awaiting
                    you appear at the top.
                </p>

                <Filters filters={filters} departments={departments} />

                <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm text-muted">
                        {leaves.total} {leaves.total === 1 ? 'request' : 'requests'}
                    </p>
                    <ExportButtons routeName="reports.leave" params={filters} />
                </div>

                <div className="overflow-x-auto rounded bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Employee</th>
                                <th className="p-3">Department</th>
                                <th className="p-3">Type</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Status</th>
                                <th className="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.map((leave) => (
                                <LeaveRow key={leave.id} leave={leave} onView={setViewing} />
                            ))}

                            {leaves.data.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="p-6 text-center text-gray-500">No leave requests match these filters.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={leaves.links} />
            </div>

            <EmployeeDetails employee={viewing} show={!!viewing} onClose={() => setViewing(null)} />
        </AuthenticatedLayout>
    );
}
