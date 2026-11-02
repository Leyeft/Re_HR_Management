import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        start_date: '',
        end_date: '',
        reason: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('leave.store'));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Request Leave</PageHeader>}>
            <Head title="Request Leave" />

            <div className="mx-auto max-w-xl py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel htmlFor="start_date" value="Start Date" />
                        <TextInput id="start_date" type="date" className="mt-1 block w-full" value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)} required />
                        <InputError message={errors.start_date} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="end_date" value="End Date" />
                        <TextInput id="end_date" type="date" className="mt-1 block w-full" value={data.end_date}
                            min={data.start_date || undefined}
                            onChange={(e) => setData('end_date', e.target.value)} required />
                        <InputError message={errors.end_date} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="reason" value="Reason" />
                        <textarea
                            id="reason"
                            rows="4"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            required
                        />
                        <InputError message={errors.reason} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <PrimaryButton disabled={processing}>Submit Request</PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
