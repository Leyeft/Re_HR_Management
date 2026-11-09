import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useSearchParams } from 'react-router-dom';
import EmployeeDetailsModal from '../../components/EmployeeDetailsModal';
import ExportLinks from '../../components/ExportLinks';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import ReasonModal from '../../components/ReasonModal';
import Spinner, { PageLoader } from '../../components/Spinner';
import { fetchDepartments } from '../../store/departmentsSlice';
import { approveAsHr, fetchAllLeave, rejectAsHr } from '../../store/leaveSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

const FILTERS = ['search', 'department', 'status', 'type', 'from', 'to'];
const EMPTY = Object.fromEntries(FILTERS.map((k) => [k, '']));

/**
 * /hr/leave — every leave request (HR/Admin). Filters live in the URL and run on
 * the server; requests waiting for HR come first. The CSV/PDF export uses the
 * same filters, so the file matches the screen.
 */
export default function AllLeave() {
    const dispatch = useDispatch();
    const [params, setParams] = useSearchParams();
    const { items, meta, status, error } = useSelector((state) => state.leave.all);
    const departments = useSelector((state) => state.departments.items);
    const [viewing, setViewing] = useState(null);
    const [rejecting, setRejecting] = useState(null);
    const [busyId, setBusyId] = useState(null);

    const applied = { ...EMPTY, ...Object.fromEntries([...params].filter(([k]) => FILTERS.includes(k))) };
    const [values, setValues] = useState(applied);
    const query = params.toString();

    const load = () => dispatch(fetchAllLeave(Object.fromEntries(params)));
    useEffect(() => {
        load();
        setValues({ ...EMPTY, ...Object.fromEntries([...params].filter(([k]) => FILTERS.includes(k))) });
    }, [query]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!departments.length) dispatch(fetchDepartments());
    }, [dispatch, departments.length]);

    const apply = (next) => setParams(Object.fromEntries(Object.entries(next).filter(([, v]) => v)));
    const set = (key) => (e) => setValues((v) => ({ ...v, [key]: e.target.value }));
    const anyFilter = FILTERS.some((k) => applied[k]);

    const approve = async (leave) => {
        setBusyId(leave.id);
        try {
            const res = await dispatch(approveAsHr(leave.id)).unwrap();
            dispatch(toastSuccess(`${leave.employee?.user?.name}: ${res.message}`));
        } catch (e) {
            dispatch(toastError(e.message));
        } finally {
            setBusyId(null);
        }
    };

    const field = 'mt-1 block w-full py-1.5 text-sm';
    const label = 'block text-xs font-medium text-muted';

    return (
        <Page title="All leave" actions={<ExportLinks path="/reports/leave" params={applied} />}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply(values);
                }}
                className="grid gap-3 rounded-lg bg-surface p-4 shadow sm:grid-cols-2 lg:grid-cols-6"
                role="search"
            >
                <label className={`${label} lg:col-span-2`}>
                    Employee
                    <TextInput type="search" className={field} value={values.search} onChange={set('search')} placeholder="Name or email" />
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
                        <option value="pending_head">Awaiting Head</option>
                        <option value="pending_hr">Awaiting HR</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </SelectInput>
                </label>
                <label className={label}>
                    From
                    <TextInput type="date" className={field} value={values.from} onChange={set('from')} />
                </label>
                <label className={label}>
                    To
                    <TextInput type="date" className={field} value={values.to} min={values.from || undefined} onChange={set('to')} />
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
                <div className="flex items-end gap-2 sm:col-span-1 lg:col-span-5 lg:justify-end">
                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">Apply</button>
                    {anyFilter && (
                        <button type="button" onClick={() => setParams({})} className="rounded-md border border-line bg-surface px-4 py-2 text-sm hover:bg-bg">
                            Reset
                        </button>
                    )}
                </div>
            </form>

            {status === 'failed' && <LoadError message={error} onRetry={load} />}

            <section className="relative rounded-lg bg-surface shadow">
                {status === 'loading' && items.length > 0 && (
                    <div className="absolute right-4 top-3"><Spinner className="h-5 w-5 text-primary-text" label="Updating" /></div>
                )}
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading leave requests…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">Employee</th>
                                    <th className="p-3">Department</th>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3 text-right">Days</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((leave) => (
                                    <tr key={leave.id} className={`border-t border-line align-top ${leave.status === 'pending_hr' ? 'bg-info-bg' : ''}`}>
                                        <td className="p-3">
                                            <button type="button" onClick={() => setViewing(leave.employee)} className="font-medium text-primary-text hover:underline">
                                                {leave.employee?.user?.name}
                                            </button>
                                        </td>
                                        <td className="p-3">{leave.employee?.department?.name ?? '—'}</td>
                                        <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                        <td className="whitespace-nowrap p-3">{leave.start_date} → {leave.end_date}</td>
                                        <td className="p-3 text-right tabular-nums">{leave.days}</td>
                                        <td className="p-3"><LeaveStatus leave={leave} /></td>
                                        <td className="whitespace-nowrap p-3 text-right">
                                            {leave.status === 'pending_hr' ? (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => approve(leave)}
                                                        disabled={busyId === leave.id}
                                                        className="me-2 rounded-md bg-success px-3 py-1.5 text-on-status hover:opacity-90 disabled:opacity-50"
                                                    >
                                                        {busyId === leave.id ? '…' : 'Approve'}
                                                    </button>
                                                    <button type="button" onClick={() => setRejecting(leave)} className="rounded-md border border-danger px-3 py-1.5 text-danger hover:bg-danger-bg">
                                                        Reject
                                                    </button>
                                                </>
                                            ) : (
                                                <span className="text-muted">—</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {status === 'succeeded' && !items.length && (
                                    <tr>
                                        <td colSpan="7" className="p-6 text-center text-muted">
                                            {anyFilter ? 'No leave requests match these filters.' : 'No leave requests yet.'}
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
                    const res = await dispatch(rejectAsHr({ id: rejecting.id, reason })).unwrap();
                    dispatch(toastSuccess(res.message));
                }}
            />
        </Page>
    );
}
