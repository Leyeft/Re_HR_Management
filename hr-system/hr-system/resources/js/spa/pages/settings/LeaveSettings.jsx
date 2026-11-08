import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import Page, { Card, LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import { addHoliday, fetchHolidays, removeHoliday } from '../../store/holidaysSlice';
import { fetchLeaveSettings, saveLeaveSettings } from '../../store/settingsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// "25 Dec" for yearly holidays, "9 Apr 2026" for one-off dates.
function formatHoliday(h) {
    const [y, m, d] = h.date.split('-').map(Number);
    return h.recurring ? `${d} ${MONTHS[m - 1]}` : `${d} ${MONTHS[m - 1]} ${y}`;
}

// /settings/leave — rollover policy and public holidays (HR/Admin).
export default function LeaveSettings() {
    const dispatch = useDispatch();
    const { leave, status, error } = useSelector((state) => state.settings);

    useEffect(() => {
        dispatch(fetchLeaveSettings());
        dispatch(fetchHolidays());
    }, [dispatch]);

    return (
        <Page title="Leave settings" width="max-w-3xl">
            {status === 'failed' && <LoadError message={error} onRetry={() => dispatch(fetchLeaveSettings())} />}
            {leave ? <RolloverForm settings={leave} /> : status !== 'failed' && <PageLoader label="Loading settings…" />}
            <Holidays />
        </Page>
    );
}

function RolloverForm({ settings }) {
    const dispatch = useDispatch();
    const form = useApiForm({ rollover_enabled: settings.rollover_enabled, rollover_max_days: settings.rollover_max_days });
    const { data, errors } = form;
    const changed = data.rollover_enabled !== settings.rollover_enabled || Number(data.rollover_max_days) !== settings.rollover_max_days;

    const submit = (e) => {
        e.preventDefault();
        form.submit(
            (values) => dispatch(saveLeaveSettings({ ...values, rollover_max_days: Number(values.rollover_max_days) })).unwrap(),
            { onSuccess: (res) => dispatch(toastSuccess(res.message)) },
        );
    };

    return (
        <Card
            title="Annual leave rollover"
            description="When on, unused annual leave from last year is added to this year's allowance, up to the maximum below. When off, unused days are lost at the end of the year."
        >
            <form onSubmit={submit} className="space-y-5" noValidate>
                <label className="flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        className="h-5 w-5 rounded border-line text-accent shadow-sm focus:ring-accent"
                        checked={data.rollover_enabled}
                        onChange={form.field('rollover_enabled')}
                    />
                    <span className="text-sm font-medium text-content">Allow unused annual leave to roll over</span>
                </label>

                <div className={data.rollover_enabled ? '' : 'opacity-50'}>
                    <InputLabel htmlFor="rollover_max_days" value="Maximum days carried over" />
                    <TextInput
                        id="rollover_max_days"
                        type="number"
                        min="0"
                        max="60"
                        className="mt-1 block w-32"
                        value={data.rollover_max_days}
                        disabled={!data.rollover_enabled}
                        onChange={form.field('rollover_max_days')}
                    />
                    <p className="mt-1 text-xs text-muted">Example: with a maximum of 5, someone who left 8 days unused last year gets 5 extra days this year.</p>
                    <InputError message={errors.rollover_max_days} className="mt-1" />
                </div>

                <div className="flex justify-end">
                    <PrimaryButton disabled={form.processing || !changed}>{form.processing ? 'Saving…' : 'Save settings'}</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}

function Holidays() {
    const dispatch = useDispatch();
    const { items, status } = useSelector((state) => state.holidays);
    const empty = { date: '', name: '', recurring: false };
    const form = useApiForm(empty);
    const [removing, setRemoving] = useState(null);

    const add = (e) => {
        e.preventDefault();
        form.submit((values) => dispatch(addHoliday(values)).unwrap(), {
            onSuccess: (res) => {
                dispatch(toastSuccess(res.message));
                form.reset(empty);
            },
        });
    };

    const remove = async (holiday) => {
        setRemoving(holiday.id);
        try {
            const res = await dispatch(removeHoliday(holiday.id)).unwrap();
            dispatch(toastSuccess(`${holiday.name}: ${res.message}`));
        } catch (e) {
            dispatch(toastError(e.message));
        } finally {
            setRemoving(null);
        }
    };

    return (
        <Card title="Public holidays" description="Leave never counts weekends or these days. Example: if Tuesday is a holiday, leave from Monday to Wednesday is 2 days.">
            <form onSubmit={add} className="grid gap-3 sm:grid-cols-[auto_1fr_auto] sm:items-end" noValidate>
                <div>
                    <InputLabel htmlFor="holiday_date" value="Date" />
                    <TextInput id="holiday_date" type="date" className="mt-1 block w-full" value={form.data.date} onChange={form.field('date')} />
                </div>
                <div>
                    <InputLabel htmlFor="holiday_name" value="Holiday name" />
                    <TextInput id="holiday_name" className="mt-1 block w-full" value={form.data.name} placeholder="e.g. Christmas Day" maxLength={100} onChange={form.field('name')} />
                </div>
                <PrimaryButton disabled={form.processing}>{form.processing ? 'Adding…' : 'Add'}</PrimaryButton>
                <label className="flex items-center gap-2 text-sm text-content sm:col-span-3">
                    <input type="checkbox" className="rounded border-line text-accent shadow-sm focus:ring-accent" checked={form.data.recurring} onChange={form.field('recurring')} />
                    Same date every year
                </label>
                <div className="sm:col-span-3">
                    <InputError message={form.errors.date} />
                    <InputError message={form.errors.name} />
                </div>
            </form>

            <div className="mt-6">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading holidays…" />
                ) : items.length === 0 ? (
                    <p className="rounded-md bg-bg px-4 py-3 text-sm text-muted">No holidays added yet.</p>
                ) : (
                    <ul className="divide-y divide-line rounded-md border border-line">
                        {items.map((h) => (
                            <li key={h.id} className="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                                <div>
                                    <span className="font-medium text-content">{h.name}</span>
                                    <span className="ms-2 text-muted">{formatHoliday(h)}</span>
                                    {h.recurring && <span className="ms-2 rounded bg-accent-tint px-2 py-0.5 text-xs font-medium text-accent-text">every year</span>}
                                </div>
                                <button type="button" onClick={() => remove(h)} disabled={removing === h.id} className="text-xs font-medium text-danger hover:underline disabled:opacity-50">
                                    {removing === h.id ? 'Removing…' : 'Remove'}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Card>
    );
}
