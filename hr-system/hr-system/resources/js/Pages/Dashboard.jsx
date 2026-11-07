import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar, Pie } from 'react-chartjs-2';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

// Personal dashboard for staff who don't manage the organisation.
function PersonalDashboard({ myLeave, isHead }) {
    const tiles = [
        { label: 'Pending', value: myLeave?.pending ?? 0, tone: 'text-warning' },
        { label: 'Approved', value: myLeave?.approved ?? 0, tone: 'text-success' },
        { label: 'Rejected', value: myLeave?.rejected ?? 0, tone: 'text-danger' },
        { label: 'Total', value: myLeave?.total ?? 0, tone: 'text-primary' },
    ];

    return (
        <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            {!myLeave && (
                <div className="rounded-lg bg-info-bg p-4 text-sm text-info">
                    Your account isn&apos;t linked to an employee profile yet.
                </div>
            )}

            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {tiles.map((t) => (
                    <div key={t.label} className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-sm text-muted">My {t.label} Leave</h3>
                        <p className={`text-3xl font-bold ${t.tone}`}>{t.value}</p>
                    </div>
                ))}
            </div>

            <div className="flex flex-wrap gap-3">
                <Link href={route('leave.index')} className="rounded bg-primary px-4 py-2 text-white hover:bg-primary-hover">
                    My Leave Requests
                </Link>
                <Link href={route('leave.create')} className="rounded border border-line bg-white px-4 py-2 text-content hover:bg-bg">
                    Request Leave
                </Link>
                {isHead && (
                    <Link href={route('leave.approvals')} className="rounded border border-line bg-white px-4 py-2 text-content hover:bg-bg">
                        Leave Approvals
                    </Link>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({ canSeeOrg, myLeave, isHead, totalStaff, totalDepartments, labels, data, leaveStats }) {
    if (!canSeeOrg) {
        return (
            <AuthenticatedLayout header={<PageHeader>Dashboard</PageHeader>}>
                <Head title="Dashboard" />
                <PersonalDashboard myLeave={myLeave} isHead={isHead} />
            </AuthenticatedLayout>
        );
    }

    const departmentChart = {
        labels,
        datasets: [
            {
                data,
                backgroundColor: ['#4f46e5', '#22c55e', '#f97316', '#ef4444', '#14b8a6', '#eab308'],
            },
        ],
    };

    const leaveChart = {
        labels: ['Awaiting Head', 'Awaiting HR', 'Approved', 'Rejected'],
        datasets: [
            {
                label: 'Leave Requests',
                data: [
                    leaveStats.pending_head,
                    leaveStats.pending_hr,
                    leaveStats.approved,
                    leaveStats.rejected,
                ],
                backgroundColor: ['#9A5B00', '#2F6FCC', '#187A46', '#C93A3A'],
            },
        ],
    };

    const leaveChartOptions = {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Dashboard</PageHeader>}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Stats */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded bg-white p-6 shadow">
                        <h3 className="text-sm text-gray-500">Total Staff</h3>
                        <p className="text-3xl font-bold">{totalStaff}</p>
                    </div>

                    <div className="rounded bg-white p-6 shadow">
                        <h3 className="text-sm text-gray-500">Total Departments</h3>
                        <p className="text-3xl font-bold">{totalDepartments}</p>
                    </div>
                </div>

                {/* Pie chart */}
                <div className="rounded bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-semibold">Staff by Department</h3>
                    <div className="mx-auto max-w-md">
                        <Pie data={departmentChart} />
                    </div>
                </div>

                {/* Bar chart */}
                <div className="rounded bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-semibold">Leave Request Status</h3>
                    <div className="mx-auto max-w-md">
                        <Bar data={leaveChart} options={leaveChartOptions} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
