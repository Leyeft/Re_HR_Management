import LeaveStatus from '@/Components/LeaveStatus';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ leaves }) {
    return (
        <AuthenticatedLayout header={<PageHeader>My Leave Requests</PageHeader>}>
            <Head title="My Leave Requests" />

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-4 flex justify-end">
                    <Link href={route('leave.create')} className="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        + Request Leave
                    </Link>
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Reason</th>
                                <th className="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.map((leave) => (
                                <tr key={leave.id} className="border-t">
                                    <td className="p-3">{leave.start_date} → {leave.end_date}</td>
                                    <td className="p-3">{leave.reason}</td>
                                    <td className="p-3"><LeaveStatus leave={leave} /></td>
                                </tr>
                            ))}

                            {leaves.data.length === 0 && (
                                <tr>
                                    <td colSpan="3" className="p-6 text-center text-gray-500">No leave requests yet.</td>
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
