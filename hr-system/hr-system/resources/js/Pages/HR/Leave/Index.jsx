import LeaveStatus from '@/Components/LeaveStatus';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ leaves }) {
    return (
        <AuthenticatedLayout header={<PageHeader>All Leave Requests</PageHeader>}>
            <Head title="All Leave Requests" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="overflow-x-auto rounded bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Employee</th>
                                <th className="p-3">Department</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.map((leave) => (
                                <tr key={leave.id} className="border-t">
                                    <td className="p-3">{leave.employee?.user?.name}</td>
                                    <td className="p-3">{leave.employee?.department?.name ?? '—'}</td>
                                    <td className="p-3">{leave.start_date} → {leave.end_date}</td>
                                    <td className="p-3"><LeaveStatus leave={leave} /></td>
                                </tr>
                            ))}

                            {leaves.data.length === 0 && (
                                <tr>
                                    <td colSpan="4" className="p-6 text-center text-gray-500">No leave requests found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={leaves.links} />
            </div>
        </AuthenticatedLayout>
    );
}
