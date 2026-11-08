import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import ImportHolidaysModal from '../../components/ImportHolidaysModal';
import Page, { Card, LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import { addHoliday, clearAllHolidays, fetchHolidays, removeHoliday } from '../../store/holidaysSlice';
import { fetchLeaveSettings, saveLeaveSettings } from '../../store/settingsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

// "Mon 13 Apr" (the year is shown as the group heading).
function formatHoliday(h) {
    const date = new Date(`${h.date}T00:00:00`);
    return date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
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
    const empty = { date: '', name: '' };
    const form = useApiForm(empty);
    const [removing, setRemoving] = useState(null);
    const [importing, setImporting] = useState(false);
    const [clearing, setClearing] = useState(false);

    // Holidays grouped by year, e.g. { 2026: [...], 2027: [...] }
    const byYear = items.reduce((groups, h) => {
        (groups[h.date.slice(0, 4)] ??= []).push(h);
        return groups;
    }, {});

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
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-accent-tint px-4 py-3">
                <p className="text-sm text-accent-text">Upload each year's holidays from a spreadsheet. Dates already saved get the name from your file.</p>
                <button
                    type="button"
                    onClick={() => setImporting(true)}
                    className="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-hover"
                >
                    Import from Excel / CSV
                </button>
            </div>
            <ImportHolidaysModal show={importing} onClose={() => setImporting(false)} />

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
                <div className="sm:col-span-3">
                    <InputError message={form.errors.date} />
                    <InputError message={form.errors.name} />
                </div>
            </form>

            <div className="mt-6">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading holidays…" />
                ) : items.length === 0 ? (
                    <p className="rounded-md bg-bg px-4 py-3 text-sm text-muted">No holidays yet. Add one above or import a list.</p>
                ) : (
                    <>
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <p className="text-sm text-muted">{items.length} holiday{items.length === 1 ? '' : 's'}</p>
                            <button type="button" onClick={() => setClearing(true)} className="rounded-md border border-danger px-3 py-1.5 text-sm font-medium text-danger hover:bg-danger-bg">
                                Clear all
                            </button>
                        </div>
                        <div className="space-y-4">
                            {Object.entries(byYear).map(([year, list]) => (
                                <section key={year}>
                                    <h4 className="mb-1 text-xs font-semibold uppercase tracking-wide text-muted">
                                        {year} · {list.length}
                                    </h4>
                                    <ul className="divide-y divide-line rounded-md border border-line">
                                        {list.map((h) => (
                                            <li key={h.id} className="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                                                <div>
                                                    <span className="inline-block w-28 text-muted">{formatHoliday(h)}</span>
                                                    <span className="font-medium text-content">{h.name}</span>
                                                </div>
                                                <button type="button" onClick={() => remove(h)} disabled={removing === h.id} className="text-xs font-medium text-danger hover:underline disabled:opacity-50">
                                                    {removing === h.id ? 'Removing…' : 'Remove'}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    </>
                )}
            </div>

            <ClearHolidaysModal show={clearing} count={items.length} onClose={() => setClearing(false)} />
        </Card>
    );
}

// "Clear all" confirmation — removes every holiday so a new year's list can be uploaded.
function ClearHolidaysModal({ show, count, onClose }) {
    const dispatch = useDispatch();
    const [busy, setBusy] = useState(false);

    const confirm = async () => {
        setBusy(true);
        try {
            const res = await dispatch(clearAllHolidays()).unwrap();
            dispatch(toastSuccess(res.message));
            onClose();
        } catch (e) {
            dispatch(toastError(e.message));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h2 className="text-lg font-medium text-content">Remove all {count} holidays?</h2>
                <p className="mt-2 text-sm text-muted">
                    Every public holiday is deleted, for all years. Use this to start fresh before importing a new list.
                    Leave that was already requested keeps its day count. This can't be undone.
                </p>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>Cancel</SecondaryButton>
                    <DangerButton type="button" onClick={confirm} disabled={busy}>{busy ? 'Removing…' : 'Remove all'}</DangerButton>
                </div>
            </div>
        </Modal>
    );
}
