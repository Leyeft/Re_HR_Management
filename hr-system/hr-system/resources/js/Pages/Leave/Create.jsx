import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LeaveBalance from '@/Components/LeaveBalance';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { inclusiveDays } from '@/Utils/leave';
import { Head, useForm } from '@inertiajs/react';

const today = new Date().toISOString().slice(0, 10);

export default function Create({ leaveSummary }) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'annual',
        start_date: '',
        end_date: '',
        reason: '',
    });

    const isSick = data.type === 'sick';
    const requestedDays = inclusiveDays(data.start_date, data.end_date);

    // Which allowance applies (professional has none).
    const limit =
        data.type === 'annual'
            ? leaveSummary?.annual?.remaining
            : isSick
              ? leaveSummary?.sick?.remaining
              : null;
    const overLimit = limit != null && requestedDays !== null && requestedDays > limit;

    const note = {
        annual: 'Counts against your annual allowance. Goes to your Head, then HR.',
        sick: 'Log the days you were out (today or earlier). Recorded immediately — no approval needed.',
        professional: 'For company benefit (seminars, field trips). No limit. Goes to your Head, then HR.',
    }[data.type];

    const submit = (e) => {
        e.preventDefault();
        post(route('leave.store'));
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Request Leave</PageHeader>}>
            <Head title="Request Leave" />

            <div className="mx-auto max-w-xl space-y-6 py-8 sm:px-6 lg:px-8">
                <LeaveBalance summary={leaveSummary} />

                <form onSubmit={submit} className="rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel htmlFor="type" value="Leave Type" />
                        <SelectInput id="type" className="mt-1 block w-full" value={data.type}
                            onChange={(e) => setData('type', e.target.value)}>
                            <option value="annual">Annual leave</option>
                            <option value="sick">Sick leave</option>
                            <option value="professional">Professional leave</option>
                        </SelectInput>
                        <p className="mt-1 text-xs text-muted">{note}</p>
                        <InputError message={errors.type} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="start_date" value="Start Date" />
                        <TextInput id="start_date" type="date" className="mt-1 block w-full" value={data.start_date}
                            max={isSick ? today : undefined}
                            min={isSick ? undefined : today}
                            onChange={(e) => setData('start_date', e.target.value)} required />
                        <InputError message={errors.start_date} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="end_date" value="End Date" />
                        <TextInput id="end_date" type="date" className="mt-1 block w-full" value={data.end_date}
                            max={isSick ? today : undefined}
                            min={data.start_date || (isSick ? undefined : today)}
                            onChange={(e) => setData('end_date', e.target.value)} required />
                        <InputError message={errors.end_date} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="reason" value="Reason" />
                        <textarea
                            id="reason"
                            rows="4"
                            className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            required
                        />
                        <InputError message={errors.reason} className="mt-1" />
                    </div>

                    {requestedDays !== null && (
                        <p className={`mt-4 text-sm ${overLimit ? 'text-danger' : 'text-muted'}`}>
                            This request is {requestedDays} day{requestedDays === 1 ? '' : 's'}.
                            {overLimit &&
                                ` That's more than your ${limit} remaining ${isSick ? 'sick days' : 'days'} this year — please visit the HR office for more details, or if you need help.`}
                        </p>
                    )}

                    <div className="mt-6 flex justify-end">
                        <PrimaryButton disabled={processing || overLimit}>
                            {isSick ? 'Record Sick Leave' : 'Submit Request'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
