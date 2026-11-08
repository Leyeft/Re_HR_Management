import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import EmployeeDetailsModal from '../../components/EmployeeDetailsModal';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import { PageLoader } from '../../components/Spinner';
import { selectAuth } from '../../store/authSlice';
import { fetchDeptHistory } from '../../store/leaveSlice';

// /approvals/history — all leave in my department, newest first (Head).
export default function DeptHistory() {
    const dispatch = useDispatch();
    const { employee } = useSelector(selectAuth);
    const { items, meta, status, error } = useSelector((state) => state.leave.history);
    const [viewing, setViewing] = useState(null);

    const load = (page = 1) => dispatch(fetchDeptHistory({ page }));
    useEffect(() => {
        load();
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <Page title={`${employee?.department?.name ?? 'Department'} leave history`} width="max-w-6xl">
            {status === 'failed' && <LoadError message={error} onRetry={() => load()} />}

            <section className="rounded-lg bg-white shadow">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading history…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">Employee</th>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3 text-right">Days</th>
                                    <th className="p-3">Reason</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Signed off by</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((leave) => (
                                    <tr key={leave.id} className="border-t border-line align-top">
                                        <td className="p-3">
                                            <button type="button" onClick={() => setViewing(leave.employee)} className="font-medium text-primary hover:underline">
                                                {leave.employee?.user?.name}
                                            </button>
                                        </td>
                                        <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                        <td className="whitespace-nowrap p-3">{leave.start_date} → {leave.end_date}</td>
                                        <td className="p-3 text-right tabular-nums">{leave.days}</td>
                                        <td className="p-3">{leave.reason}</td>
                                        <td className="p-3"><LeaveStatus leave={leave} /></td>
                                        <td className="p-3 text-xs text-muted">
                                            {leave.head_approver && <div>Head: {leave.head_approver.name}</div>}
                                            {leave.hr_approver && <div>HR: {leave.hr_approver.name}</div>}
                                            {!leave.head_approver && !leave.hr_approver && '—'}
                                        </td>
                                    </tr>
                                ))}
                                {status === 'succeeded' && !items.length && (
                                    <tr><td colSpan="7" className="p-6 text-center text-muted">No leave in your department yet.</td></tr>
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
        </Page>
    );
}
