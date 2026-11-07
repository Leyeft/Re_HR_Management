import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LeaveBalance from '@/Components/LeaveBalance';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import Modal from '@/Components/Modal';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const CANCELLABLE = ['pending_head', 'pending_hr', 'approved'];

export default function Index({ leaves, hasProfile = true, leaveSummary = null }) {
    const [cancelling, setCancelling] = useState(null);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        cancellation_reason: '',
    });

    const openCancel = (leave) => {
        clearErrors();
        reset();
        setCancelling(leave);
    };

    const submitCancel = (e) => {
        e.preventDefault();
        post(route('leave.cancel', cancelling.id), {
            preserveScroll: true,
            onSuccess: () => setCancelling(null),
        });
    };

    return (
        <AuthenticatedLayout header={<PageHeader>My Leave Requests</PageHeader>}>
            <Head title="My Leave Requests" />

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                {!hasProfile && (
                    <div className="mb-4 rounded-lg bg-info-bg p-4 text-sm text-info">
                        Your account isn&apos;t linked to an employee profile, so you have no
                        personal leave requests. Admin and HR accounts manage leave for others
                        rather than submitting their own.
                    </div>
                )}

                {hasProfile && leaveSummary && (
                    <div className="mb-4">
                        <LeaveBalance summary={leaveSummary} />
                    </div>
                )}

                {hasProfile && (
                    <div className="mb-4 flex justify-end">
                        <Link href={route('leave.create')} className="rounded bg-primary px-4 py-2 text-white hover:bg-primary-hover">
                            + Request Leave
                        </Link>
                    </div>
                )}

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Type</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Reason</th>
                                <th className="p-3">Status</th>
                                <th className="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.map((leave) => (
                                <tr key={leave.id} className="border-t align-top">
                                    <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                    <td className="p-3">{leave.start_date} → {leave.end_date}</td>
                                    <td className="p-3">{leave.reason}</td>
                                    <td className="p-3"><LeaveStatus leave={leave} /></td>
                                    <td className="p-3 text-right">
                                        {CANCELLABLE.includes(leave.status) ? (
                                            <button
                                                type="button"
                                                onClick={() => openCancel(leave)}
                                                className="text-sm text-danger hover:underline"
                                            >
                                                Cancel
                                            </button>
                                        ) : (
                                            <span className="text-sm text-muted">—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}

                            {leaves.data.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="p-6 text-center text-gray-500">No leave requests yet.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={leaves.links} />
            </div>

            <Modal show={!!cancelling} onClose={() => setCancelling(null)} maxWidth="md">
                <form onSubmit={submitCancel} className="p-6">
                    <h2 className="text-lg font-medium text-content">Cancel this leave request?</h2>
                    <p className="mt-2 text-sm text-muted">
                        {cancelling?.start_date} → {cancelling?.end_date}. Please tell us why you&apos;re cancelling.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="cancellation_reason" value="Reason for cancelling" />
                        <textarea
                            id="cancellation_reason"
                            rows="3"
                            className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                            value={data.cancellation_reason}
                            onChange={(e) => setData('cancellation_reason', e.target.value)}
                            required
                        />
                        <InputError message={errors.cancellation_reason} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setCancelling(null)}>
                            Keep it
                        </SecondaryButton>
                        <DangerButton disabled={processing || !data.cancellation_reason.trim()}>
                            Cancel request
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
