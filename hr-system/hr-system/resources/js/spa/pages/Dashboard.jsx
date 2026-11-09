import LeaveBalance from '@/Components/LeaveBalance';
import { useEffect } from 'react';
import { Bar } from 'react-chartjs-2';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { barOptions, barStyle, cssVar } from '../components/charts';
import Page, { Card, LoadError } from '../components/Page';
import { PageLoader } from '../components/Spinner';
import { selectAuth } from '../store/authSlice';
import { fetchDashboard } from '../store/dashboardSlice';
import { selectTheme } from '../store/themeSlice';

const STATUS = [
    { key: 'pending_head', label: 'Awaiting Head', color: '--color-warning' },
    { key: 'pending_hr', label: 'Awaiting HR', color: '--color-info' },
    { key: 'approved', label: 'Approved', color: '--color-success' },
    { key: 'rejected', label: 'Rejected', color: '--color-danger' },
    { key: 'cancelled', label: 'Cancelled', color: '--color-text-muted' },
];

export default function Dashboard() {
    const dispatch = useDispatch();
    const { data, status, error } = useSelector((state) => state.dashboard);

    // Refresh every time the dashboard is opened.
    useEffect(() => {
        dispatch(fetchDashboard());
    }, [dispatch]);

    return (
        <Page title="Dashboard">
            {status === 'failed' && <LoadError message={error} onRetry={() => dispatch(fetchDashboard())} />}
            {!data && status !== 'failed' && <PageLoader label="Loading your dashboard…" />}
            {data?.scope === 'organisation' && <OrganisationDashboard data={data} />}
            {data?.scope === 'personal' && <PersonalDashboard data={data} />}
        </Page>
    );
}

/* ===== Admin / HR ===== */

function OrganisationDashboard({ data }) {
    useSelector(selectTheme); // re-render on a light/dark switch, so the charts pick up the new colours
    const awaitingHr = data.leave_by_status.pending_hr ?? 0;

    // Biggest department first, so the ranking reads top to bottom.
    const departments = [...data.staff_by_department].sort((a, b) => b.employees - a.employees);

    const staffChart = {
        labels: departments.map((d) => d.department),
        datasets: [{ data: departments.map((d) => d.employees), backgroundColor: cssVar('--color-primary'), ...barStyle }],
    };

    const statusChart = {
        labels: STATUS.map((s) => s.label),
        datasets: [{ data: STATUS.map((s) => data.leave_by_status[s.key] ?? 0), backgroundColor: STATUS.map((s) => cssVar(s.color)), ...barStyle }],
    };

    return (
        <>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Stat label="Staff" value={data.total_staff} />
                <Stat label="Departments" value={data.total_departments} />
                <Link
                    to="/hr/leave?status=pending_hr"
                    className="rounded-lg border-l-4 border-info bg-surface p-6 shadow transition hover:shadow-md"
                >
                    <p className="text-sm text-muted">Waiting for HR sign-off</p>
                    <p className="mt-1 text-4xl font-bold text-primary-text">{awaitingHr}</p>
                    <p className="mt-1 text-xs font-medium text-info">{awaitingHr ? 'Review now →' : 'All caught up'}</p>
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card title="Staff by department" description="Number of employees in each department, largest first.">
                    <div style={{ height: Math.max(160, departments.length * 40) }}>
                        <Bar
                            data={staffChart}
                            options={barOptions({ horizontal: true, tooltipLabel: (ctx) => `${ctx.raw} employee${ctx.raw === 1 ? '' : 's'}` })}
                            aria-label="Bar chart of staff by department"
                            role="img"
                        />
                    </div>
                    <DataTable caption="Staff by department" head={['Department', 'Employees']} rows={departments.map((d) => [d.department, d.employees])} />
                </Card>

                <Card title="Leave requests by status" description="All leave requests in the system.">
                    <div style={{ height: 240 }}>
                        <Bar
                            data={statusChart}
                            options={barOptions({ tooltipLabel: (ctx) => `${ctx.raw} request${ctx.raw === 1 ? '' : 's'}` })}
                            aria-label="Bar chart of leave requests by status"
                            role="img"
                        />
                    </div>
                    <DataTable caption="Leave requests by status" head={['Status', 'Requests']} rows={STATUS.map((s) => [s.label, data.leave_by_status[s.key] ?? 0])} />
                </Card>
            </div>
        </>
    );
}

/* ===== Everyone else ===== */

function PersonalDashboard({ data }) {
    const { employee } = useSelector(selectAuth);
    const my = data.my_leave;

    if (!my) {
        return (
            <div className="rounded-lg bg-info-bg p-4 text-sm text-info">
                Your account isn&apos;t linked to an employee profile yet. Please contact HR.
            </div>
        );
    }

    return (
        <>
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-surface p-5 shadow">
                <div>
                    <p className="text-sm text-muted">{employee?.department?.name} · Employee ID</p>
                    <p className="font-mono text-2xl font-semibold text-primary-text">{data.employee_code}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link to="/leave/new" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover">
                        Request leave
                    </Link>
                    {data.is_head && (
                        <Link to="/approvals" className="rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium text-content hover:bg-bg">
                            Approvals
                        </Link>
                    )}
                </div>
            </div>

            <LeaveBalance summary={data.leave_summary} />

            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                <Stat label="Pending" value={my.pending} tone="text-warning" />
                <Stat label="Approved" value={my.approved} tone="text-success" />
                <Stat label="Rejected" value={my.rejected} tone="text-danger" />
                <Stat label="All requests" value={my.total} />
            </div>
        </>
    );
}

/* ===== Pieces ===== */

function Stat({ label, value, tone = 'text-primary-text' }) {
    return (
        <div className="rounded-lg bg-surface p-6 shadow">
            <p className="text-sm text-muted">{label}</p>
            <p className={`mt-1 text-3xl font-bold ${tone}`}>{value}</p>
        </div>
    );
}

// The chart's numbers as a table — for screen readers and anyone who prefers figures.
function DataTable({ caption, head, rows }) {
    return (
        <details className="mt-4 text-sm">
            <summary className="cursor-pointer text-muted hover:text-content">View as table</summary>
            <table className="mt-2 w-full">
                <caption className="sr-only">{caption}</caption>
                <thead className="text-left text-xs uppercase text-muted">
                    <tr>
                        {head.map((h, i) => (
                            <th key={h} className={`py-1 ${i ? 'text-right' : ''}`}>{h}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map(([name, value]) => (
                        <tr key={name} className="border-t border-line">
                            <td className="py-1">{name}</td>
                            <td className="py-1 text-right tabular-nums">{value}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </details>
    );
}
