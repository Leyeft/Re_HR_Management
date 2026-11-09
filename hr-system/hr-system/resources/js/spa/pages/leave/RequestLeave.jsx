import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LeaveBalance from '@/Components/LeaveBalance';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { workingDays } from '@/Utils/leave';
import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate } from 'react-router-dom';
import Page from '../../components/Page';
import useApiForm from '../../hooks/useApiForm';
import { fetchHolidays, selectHolidayLookup } from '../../store/holidaysSlice';
import { fetchMySummary, submitLeave } from '../../store/leaveSlice';
import { toastSuccess } from '../../store/toastSlice';

const today = new Date().toISOString().slice(0, 10);

const NOTES = {
    annual: 'Counts against your annual allowance. Goes to your Head, then HR.',
    sick: 'Log the days you were out (today or earlier). Recorded straight away — no approval needed.',
    professional: 'For company benefit (seminars, field trips). No limit. Goes to your Head, then HR.',
};

// /leave/new — the working-day count updates as you pick dates (weekends and holidays skipped).
export default function RequestLeave() {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const summary = useSelector((state) => state.leave.summary.data);
    const holidays = useSelector(selectHolidayLookup);
    const form = useApiForm({ type: 'annual', start_date: '', end_date: '', reason: '' });
    const { data, errors, field } = form;

    useEffect(() => {
        dispatch(fetchMySummary());
        dispatch(fetchHolidays());
    }, [dispatch]);

    const isSick = data.type === 'sick';
    const days = workingDays(data.start_date, data.end_date, holidays);
    const limit = data.type === 'annual' ? summary?.annual?.remaining : isSick ? summary?.sick?.remaining : null;
    const overLimit = limit != null && days != null && days > limit;
    const noWorkingDays = days === 0;

    const submit = (e) => {
        e.preventDefault();
        form.submit((values) => dispatch(submitLeave(values)).unwrap(), {
            onSuccess: (res) => {
                dispatch(toastSuccess(res.message));
                navigate('/leave');
            },
        });
    };

    return (
        <Page title="Request leave" width="max-w-2xl">
            <LeaveBalance summary={summary} />

            <form onSubmit={submit} className="space-y-4 rounded-lg bg-surface p-6 shadow" noValidate>
                <div>
                    <InputLabel htmlFor="type" value="Leave type" />
                    <SelectInput id="type" className="mt-1 block w-full" value={data.type} onChange={field('type')}>
                        <option value="annual">Annual leave</option>
                        <option value="sick">Sick leave</option>
                        <option value="professional">Professional leave</option>
                    </SelectInput>
                    <p className="mt-1 text-xs text-muted">{NOTES[data.type]}</p>
                    <InputError message={errors.type} className="mt-1" />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="start_date" value="First day" />
                        <TextInput
                            id="start_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={data.start_date}
                            min={isSick ? undefined : today}
                            max={isSick ? today : undefined}
                            onChange={field('start_date')}
                            required
                        />
                        <InputError message={errors.start_date} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="end_date" value="Last day" />
                        <TextInput
                            id="end_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={data.end_date}
                            min={data.start_date || (isSick ? undefined : today)}
                            max={isSick ? today : undefined}
                            onChange={field('end_date')}
                            required
                        />
                        <InputError message={errors.end_date} className="mt-1" />
                    </div>
                </div>

                {/* Live working-day count */}
                {days !== null && (
                    <p
                        aria-live="polite"
                        className={`rounded-md px-4 py-3 text-sm ${overLimit || noWorkingDays ? 'bg-danger-bg text-danger' : 'bg-accent-tint text-accent-text'}`}
                    >
                        {noWorkingDays
                            ? 'These dates are all weekends or public holidays — there are no working days to request.'
                            : `This request is ${days} working day${days === 1 ? '' : 's'} (weekends and public holidays don't count).`}
                        {overLimit && ` That's more than your ${limit} remaining ${isSick ? 'sick days' : 'days'} this year — please visit the HR office for help.`}
                    </p>
                )}

                <div>
                    <InputLabel htmlFor="reason" value="Reason" />
                    <textarea
                        id="reason"
                        rows="4"
                        maxLength={500}
                        className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                        value={data.reason}
                        onChange={field('reason')}
                        required
                    />
                    <div className="mt-1 flex justify-between gap-2">
                        <InputError message={errors.reason} />
                        <span className="ms-auto text-xs text-muted">{data.reason.length}/500</span>
                    </div>
                </div>

                <div className="flex justify-end gap-3 border-t border-line pt-4">
                    <Link to="/leave" className="rounded-md border border-line bg-surface px-4 py-2 text-sm hover:bg-bg">Cancel</Link>
                    <PrimaryButton disabled={form.processing || overLimit || noWorkingDays}>
                        {form.processing ? 'Sending…' : isSick ? 'Record sick leave' : 'Submit request'}
                    </PrimaryButton>
                </div>
            </form>
        </Page>
    );
}
