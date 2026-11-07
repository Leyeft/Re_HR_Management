import EmployeeDetails from '@/Components/EmployeeDetails';
import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function History({ leaves }) {
    const [viewing, setViewing] = useState(null);

    return (
        <AuthenticatedLayout header={<PageHeader>Department Leave History</PageHeader>}>
            <Head title="Leave History" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Employee</th>
                                <th className="p-3">Type</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Reason</th>
                                <th className="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.map((leave) => (
                                <tr key={leave.id} className="border-t">
                                    <td className="p-3">
                                        <button
                                            type="button"
                                            onClick={() => setViewing(leave.employee)}
                                            className="font-medium text-primary hover:underline"
                                        >
                                            {leave.employee.user.name}
                                        </button>
                                    </td>
                                    <td className="p-3"><LeaveTypeBadge type={leave.type} /></td>
                                    <td className="p-3">{leave.start_date} → {leave.end_date}</td>
                                    <td className="p-3">{leave.reason}</td>
                                    <td className="p-3"><LeaveStatus leave={leave} /></td>
                                </tr>
                            ))}

                            {leaves.data.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="p-6 text-center text-gray-500">No leave records found.</td>
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
