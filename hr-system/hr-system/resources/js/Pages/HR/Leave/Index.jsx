import EmployeeDetails from '@/Components/EmployeeDetails';
import InputError from '@/Components/InputError';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
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

export default function Index({ leaves }) {
    const [viewing, setViewing] = useState(null);

    return (
        <AuthenticatedLayout header={<PageHeader>All Leave Requests</PageHeader>}>
            <Head title="All Leave Requests" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p className="mb-4 rounded-lg bg-info-bg p-3 text-sm text-info">
                    You give the final sign-off after a Department Head approves. Requests awaiting
                    you appear at the top.
                </p>

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
                                    <td colSpan="6" className="p-6 text-center text-gray-500">No leave requests found.</td>
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
