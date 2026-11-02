import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

// One table row, with its own reject-reason form.
function ApprovalRow({ leave }) {
    const { data, setData, post, processing, errors } = useForm({
        rejection_reason: '',
    });

    const approve = () => {
        router.post(route('leave.approve', leave.id), {}, { preserveScroll: true });
    };

    const reject = (e) => {
        e.preventDefault();
        post(route('leave.reject', leave.id), { preserveScroll: true });
    };

    return (
        <tr className="border-t">
            <td className="p-3">{leave.employee.user.name}</td>
            <td className="p-3">{leave.start_date} → {leave.end_date}</td>
            <td className="p-3">{leave.reason}</td>
            <td className="p-3 text-right">
                <div className="flex items-start justify-end gap-2">
                    <button
                        type="button"
                        onClick={approve}
                        className="rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700"
                    >
                        Approve
                    </button>

                    <form onSubmit={reject} className="flex flex-col items-end">
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={data.rejection_reason}
                                onChange={(e) => setData('rejection_reason', e.target.value)}
                                placeholder="Reject reason"
                                required
                                className="rounded border-gray-300 px-2 py-1 text-sm"
                            />
                            <button
                                disabled={processing}
                                className="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                            >
                                Reject
                            </button>
                        </div>
                        <InputError message={errors.rejection_reason} className="mt-1" />
                    </form>
                </div>
            </td>
        </tr>
    );
}

export default function Index({ requests }) {
    return (
        <AuthenticatedLayout header={<PageHeader>Leave Approvals</PageHeader>}>
            <Head title="Leave Approvals" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="overflow-x-auto rounded bg-white shadow">
                    <table className="w-full">
                        <thead className="bg-gray-100 text-left text-sm">
                            <tr>
                                <th className="p-3">Employee</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Reason</th>
                                <th className="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {requests.data.map((leave) => (
                                <ApprovalRow key={leave.id} leave={leave} />
                            ))}

                            {requests.data.length === 0 && (
                                <tr>
                                    <td colSpan="4" className="p-6 text-center text-gray-500">No pending requests.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={requests.links} />
            </div>
        </AuthenticatedLayout>
    );
}
