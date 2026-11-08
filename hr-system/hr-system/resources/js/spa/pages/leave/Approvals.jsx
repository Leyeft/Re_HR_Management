import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import EmployeeDetailsModal from '../../components/EmployeeDetailsModal';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import ReasonModal from '../../components/ReasonModal';
import { PageLoader } from '../../components/Spinner';
import { approveAsHead, fetchApprovals, rejectAsHead } from '../../store/leaveSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

/**
 * /approvals — Head of Department, step 1. Requests from my department waiting
 * for me, oldest first, with each person's remaining balance to help decide.
 * Approving sends the request on to HR.
 */
export default function Approvals() {
    const dispatch = useDispatch();
    const { items, meta, status, error } = useSelector((state) => state.leave.approvals);
    const [viewing, setViewing] = useState(null);
    const [rejecting, setRejecting] = useState(null);
    const [busyId, setBusyId] = useState(null);

    const load = (page = 1) => dispatch(fetchApprovals({ page }));
    useEffect(() => {
        load();
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    const approve = async (leave) => {
        setBusyId(leave.id);
        try {
            const res = await dispatch(approveAsHead(leave.id)).unwrap();
            dispatch(toastSuccess(`${leave.employee?.user?.name}: ${res.message}`));
        } catch (e) {
            dispatch(toastError(e.message));
        } finally {
            setBusyId(null);
        }
    };

    return (
        <Page title="Approvals" width="max-w-6xl">
            <p className="rounded-lg bg-info-bg p-3 text-sm text-info">
                You approve first; HR gives the final sign-off. Rejecting ends the request — the employee sees your reason.
            </p>

            {status === 'failed' && <LoadError message={error} onRetry={() => load()} />}

            <section className="rounded-lg bg-white shadow">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading requests…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">Employee</th>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3 text-right">Days</th>
                                    <th className="p-3">Annual left</th>
                                    <th className="p-3">Reason</th>
                                    <th className="p-3 text-right">Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((leave) => {
                                    const ls = leave.employee?.leave_summary;
                                    return (
                                        <tr key={leave.id} className="border-t border-line align-top">
                                            <td className="p-3">
                                                <button type="button" onClick={() => setViewing(leave.employee)} className="font-medium text-primary hover:underline">
                                                    {leave.employee?.user?.name}
                                                </button>
                                            </td>
                                            <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                            <td className="whitespace-nowrap p-3">{leave.start_date} → {leave.end_date}</td>
                                            <td className="p-3 text-right tabular-nums">{leave.days}</td>
                                            <td className="p-3 tabular-nums">{ls ? `${ls.annual.remaining}/${ls.annual.total}` : '—'}</td>
                                            <td className="p-3">{leave.reason}</td>
                                            <td className="whitespace-nowrap p-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => approve(leave)}
                                                    disabled={busyId === leave.id}
                                                    className="me-2 rounded-md bg-success px-3 py-1.5 text-white hover:opacity-90 disabled:opacity-50"
                                                >
                                                    {busyId === leave.id ? '…' : 'Approve'}
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setRejecting(leave)}
                                                    className="rounded-md border border-danger px-3 py-1.5 text-danger hover:bg-danger-bg"
                                                >
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {status === 'succeeded' && !items.length && (
                                    <tr>
                                        <td colSpan="7" className="p-8 text-center text-muted">
                                            Nothing waiting for you. 🎉
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={meta} onPage={load} label="requests" />
                </div>
            </section>

            <EmployeeDetailsModal employee={viewing} onClose={() => setViewing(null)} />
            <ReasonModal
                show={!!rejecting}
                title={`Reject ${rejecting?.employee?.user?.name ?? ''}'s request?`}
                description={rejecting && `${rejecting.start_date} → ${rejecting.end_date} (${rejecting.days} working day${rejecting.days === 1 ? '' : 's'}).`}
                label="Reason (shown to the employee)"
                confirmLabel="Reject request"
                errorField="rejection_reason"
                onClose={() => setRejecting(null)}
                onConfirm={async (reason) => {
                    const res = await dispatch(rejectAsHead({ id: rejecting.id, reason })).unwrap();
                    dispatch(toastSuccess(res.message));
                }}
            />
        </Page>
    );
}
