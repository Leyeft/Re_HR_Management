import LeaveBalance from '@/Components/LeaveBalance';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate, useParams } from 'react-router-dom';
import Avatar from '../../components/Avatar';
import DeleteEmployeeModal from '../../components/DeleteEmployeeModal';
import { formatDateTime } from '../../utils/time';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import SkillChips from '../../components/SkillChips';
import { PageLoader } from '../../components/Spinner';
import { selectCan } from '../../store/authSlice';
import { fetchEmployee, fetchEmployeeHistory } from '../../store/employeesSlice';
import { canSeeSalary, formatSalary } from '../../utils/money';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };
const fmt = (iso) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : null);

// /employees/:id — full profile for Admin/HR.
export default function EmployeeProfile() {
    const { id } = useParams();
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { item: employee, status, error } = useSelector((state) => state.employees.current);
    const history = useSelector((state) => state.employees.history);
    const can = useSelector(selectCan);
    const [deleting, setDeleting] = useState(null);

    useEffect(() => {
        dispatch(fetchEmployee(id));
        dispatch(fetchEmployeeHistory({ id }));
    }, [dispatch, id]);

    if (status === 'failed') {
        return (
            <Page title="Employee">
                <LoadError message={error} onRetry={() => dispatch(fetchEmployee(id))} />
            </Page>
        );
    }

    if (!employee || employee.id !== Number(id)) {
        return (
            <Page title="Employee">
                <PageLoader label="Loading profile…" />
            </Page>
        );
    }

    const u = employee.user;

    return (
        <Page
            title={u?.name}
            width="max-w-5xl"
            actions={
                <>
                    <a href={`/reports/employees/${employee.id}/leave-record.pdf`} className="rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium hover:bg-bg">
                        Download PDF
                    </a>
                    {/* Only an Admin can change or remove an Admin account. */}
                    {(u?.role !== 'admin' || can.manage_admins) && (
                        <>
                            <Link to={`/employees/${employee.id}/edit`} className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover">
                                Edit
                            </Link>
                            <button type="button" onClick={() => setDeleting(employee)} className="rounded-md border border-danger px-4 py-2 text-sm font-medium text-danger hover:bg-danger-bg">
                                Delete
                            </button>
                        </>
                    )}
                </>
            }
        >
            <Link to="/employees" className="text-sm text-muted hover:text-content">← Back to employees</Link>

            <section className="rounded-lg bg-surface p-6 shadow">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Avatar user={u} size="lg" />
                        <div>
                            <p className="text-sm text-muted">{u?.email}</p>
                            <p className="mt-1 font-mono text-sm text-muted">ID: {employee.employee_code}</p>
                            <p className="mt-1 text-xs text-muted">Last sign-in: {u?.last_login_at ? formatDateTime(u.last_login_at) : 'never'}</p>
                        </div>
                    </div>
                    <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">{employee.rank_label}</span>
                </div>
                <dl className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <Field label="Department">{employee.department?.name}</Field>
                    <Field label="System role">{ROLE_LABELS[u?.role]}</Field>
                    <Field label="Date of birth">{employee.date_of_birth}</Field>
                    <Field label="Date hired">{employee.date_hired}</Field>
                    <Field label="Years of service">{employee.leave_summary?.years_of_service}</Field>
                    {canSeeSalary(employee) && <Field label="Monthly salary">{formatSalary(employee.salary)}</Field>}
                </dl>
                <div className="mt-6 border-t border-line pt-4">
                    <h3 className="mb-2 text-xs uppercase tracking-wide text-muted">Skills</h3>
                    <SkillChips skills={employee.skills} />
                </div>
            </section>

            <LeaveBalance summary={employee.leave_summary} />

            <section className="rounded-lg bg-surface shadow">
                <h3 className="border-b border-line p-4 text-sm font-medium text-muted">Leave history (all years)</h3>
                {history.status === 'loading' && !history.items.length ? (
                    <PageLoader label="Loading leave history…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase text-muted">
                                <tr>
                                    <th className="p-3">Type</th>
                                    <th className="p-3">Dates</th>
                                    <th className="p-3 text-right">Days</th>
                                    <th className="p-3">Requested</th>
                                    <th className="p-3">Reason</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Approvals</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.items.map((leave) => (
                                    <tr key={leave.id} className="border-t border-line align-top">
                                        <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                        <td className="whitespace-nowrap p-3">{leave.start_date} → {leave.end_date}</td>
                                        <td className="p-3 text-right tabular-nums">{leave.days}</td>
                                        <td className="whitespace-nowrap p-3">{fmt(leave.created_at)}</td>
                                        <td className="p-3">{leave.reason}</td>
                                        <td className="p-3"><LeaveStatus leave={leave} /></td>
                                        <td className="p-3 text-xs text-muted">
                                            {leave.head_approver && <div>Head: {leave.head_approver.name}, {fmt(leave.head_approved_at)}</div>}
                                            {leave.hr_approver && <div>HR: {leave.hr_approver.name}, {fmt(leave.hr_approved_at)}</div>}
                                            {!leave.head_approver && !leave.hr_approver && '—'}
                                        </td>
                                    </tr>
                                ))}
                                {history.status === 'succeeded' && !history.items.length && (
                                    <tr><td colSpan="7" className="p-6 text-center text-muted">No leave on record.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={history.meta} onPage={(page) => dispatch(fetchEmployeeHistory({ id, page }))} label="requests" />
                </div>
            </section>

            <DeleteEmployeeModal employee={deleting} onClose={() => setDeleting(null)} onDeleted={() => navigate('/employees')} />
        </Page>
    );
}

function Field({ label, children }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-muted">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-content">{children ?? '—'}</dd>
        </div>
    );
}
