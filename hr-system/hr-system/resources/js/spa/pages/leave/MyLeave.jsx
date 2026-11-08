import LeaveBalance from '@/Components/LeaveBalance';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import SelectInput from '@/Components/SelectInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useSearchParams } from 'react-router-dom';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import ReasonModal from '../../components/ReasonModal';
import { PageLoader } from '../../components/Spinner';
import { cancelLeave, fetchMyLeave, fetchMySummary } from '../../store/leaveSlice';
import { toastSuccess } from '../../store/toastSlice';

const CANCELLABLE = ['pending_head', 'pending_hr', 'approved'];

// /leave — my balance and my requests (filter by status, paged, cancel with a reason).
export default function MyLeave() {
    const dispatch = useDispatch();
    const [params, setParams] = useSearchParams();
    const { items, meta, status, error } = useSelector((state) => state.leave.mine);
    const summary = useSelector((state) => state.leave.summary.data);
    const [cancelling, setCancelling] = useState(null);

    const query = params.toString();
    const load = () => dispatch(fetchMyLeave(Object.fromEntries(params)));

    useEffect(() => {
        load();
    }, [query]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        dispatch(fetchMySummary());
    }, [dispatch]);

    const setStatus = (value) => setParams(value ? { status: value } : {});

    return (
        <Page
            title="My leave"
            width="max-w-6xl"
            actions={
                <Link to="/leave/new" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover">
                    + Request leave
                </Link>
            }
        >
            {summary && <LeaveBalance summary={summary} />}

            {status === 'failed' && <LoadError message={error} onRetry={load} />}

            <section className="rounded-lg bg-white shadow">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4">
                    <h3 className="text-sm font-medium text-muted">My requests</h3>
                    <label className="flex items-center gap-2 text-sm text-muted">
                        Show
                        <SelectInput className="py-1.5 text-sm" value={params.get('status') ?? ''} onChange={(e) => setStatus(e.target.value)}>
                            <option value="">All</option>
                            <option value="pending_head">Awaiting Head</option>
                            <option value="pending_hr">Awaiting HR</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </SelectInput>
                    </label>
                </div>

                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading your requests…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3 text-right">Days</th>
                                    <th className="p-3">Reason</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((leave) => (
                                    <tr key={leave.id} className="border-t border-line align-top">
                                        <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                        <td className="whitespace-nowrap p-3">{leave.start_date} → {leave.end_date}</td>
                                        <td className="p-3 text-right tabular-nums">{leave.days}</td>
                                        <td className="p-3">{leave.reason}</td>
                                        <td className="p-3"><LeaveStatus leave={leave} /></td>
                                        <td className="p-3 text-right">
                                            {CANCELLABLE.includes(leave.status) ? (
                                                <button type="button" onClick={() => setCancelling(leave)} className="text-danger hover:underline">
                                                    Cancel
                                                </button>
                                            ) : (
                                                <span className="text-muted">—</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {status === 'succeeded' && !items.length && (
                                    <tr>
                                        <td colSpan="6" className="p-6 text-center text-muted">
                                            {params.get('status') ? 'No requests with this status.' : 'No leave requests yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={meta} onPage={(page) => setParams({ ...Object.fromEntries(params), page: String(page) })} label="requests" />
                </div>
            </section>

            <ReasonModal
                show={!!cancelling}
                title="Cancel this leave request?"
                description={cancelling && `${cancelling.start_date} → ${cancelling.end_date} (${cancelling.days} working day${cancelling.days === 1 ? '' : 's'}).`}
                label="Why are you cancelling?"
                confirmLabel="Cancel request"
                cancelLabel="Keep it"
                errorField="cancellation_reason"
                onClose={() => setCancelling(null)}
                onConfirm={async (reason) => {
                    const res = await dispatch(cancelLeave({ id: cancelling.id, reason })).unwrap();
                    dispatch(toastSuccess(res.message));
                    dispatch(fetchMySummary()); // the balance changes
                }}
            />
        </Page>
    );
}
