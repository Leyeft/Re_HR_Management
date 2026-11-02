import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
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

export default function Dashboard({ totalStaff, totalDepartments, labels, data, leaveStats }) {
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
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [
            {
                label: 'Leave Requests',
                data: [leaveStats.pending, leaveStats.approved, leaveStats.rejected],
                backgroundColor: ['#FACC15', '#22C55E', '#EF4444'],
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
