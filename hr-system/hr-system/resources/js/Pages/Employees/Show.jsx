import LeaveBalance from '@/Components/LeaveBalance';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { workingDays } from '@/Utils/leave';
import { Head, Link, usePage } from '@inertiajs/react';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

function Field({ label, children }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-muted">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-content">{children ?? '—'}</dd>
        </div>
    );
}

export default function Show({ employee, leaves }) {
    const u = employee.user;
    const { holidays } = usePage().props;

    return (
        <AuthenticatedLayout header={<PageHeader>Employee Profile</PageHeader>}>
            <Head title={u?.name ?? 'Employee'} />

            <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <Link href={route('employees.index')} className="text-sm text-muted hover:text-content">
                    &larr; Back to Employees
                </Link>

                {/* Header card */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-semibold text-content">{u?.name}</h2>
                            <p className="text-sm text-muted">{u?.email}</p>
                            <p className="mt-1 font-mono text-sm text-muted">ID: {employee.employee_code}</p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">
                                {employee.rank === 'head' ? 'Head of Department' : 'Employee'}
                            </span>
                            <a
                                href={route('reports.employee', employee.id)}
                                className="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-content hover:bg-bg"
                            >
                                Download PDF
                            </a>
                            <Link
                                href={route('employees.edit', employee.id)}
                                className="inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>

                    <dl className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <Field label="Department">{employee.department?.name}</Field>
                        <Field label="System role">{ROLE_LABELS[u?.role] ?? u?.role}</Field>
                        <Field label="Date of birth">{employee.date_of_birth}</Field>
                        <Field label="Date hired">{employee.date_hired}</Field>
                        <Field label="Years of service">{employee.leave_summary?.years_of_service}</Field>
                    </dl>
                </div>

                <LeaveBalance summary={employee.leave_summary} />

                {/* Full leave history (all years) */}
                <div className="rounded-lg bg-white shadow">
                    <h3 className="border-b border-line p-4 text-sm font-medium text-muted">
                        Leave history
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase text-muted">
                                <tr>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3">Days</th>
                                    <th className="p-3">Requested on</th>
                                    <th className="p-3">Reason</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Approvals</th>
                                </tr>
                            </thead>
                            <tbody>
                                {leaves.data.map((leave) => {
                                    const days = workingDays(leave.start_date, leave.end_date, holidays);
                                    return (
                                        <tr key={leave.id} className="border-t border-line align-top">
                                            <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                            <td className="p-3">{leave.start_date} → {leave.end_date}</td>
                                            <td className="p-3">{days}</td>
                                            <td className="p-3">{leave.created_at?.slice(0, 10) ?? '—'}</td>
                                            <td className="p-3">{leave.reason}</td>
                                            <td className="p-3"><LeaveStatus leave={leave} /></td>
                                            <td className="p-3 text-xs text-muted">
                                                {leave.head_approver?.name && (
                                                    <div>Head: {leave.head_approver.name}</div>
                                                )}
                                                {leave.hr_approver?.name && (
                                                    <div>HR: {leave.hr_approver.name}</div>
                                                )}
                                                {!leave.head_approver?.name && !leave.hr_approver?.name && '—'}
                                            </td>
                                        </tr>
                                    );
                                })}

                                {leaves.data.length === 0 && (
                                    <tr>
                                        <td colSpan="7" className="p-6 text-center text-muted">
                                            No leave records.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="p-4">
                        <Pagination links={leaves.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
