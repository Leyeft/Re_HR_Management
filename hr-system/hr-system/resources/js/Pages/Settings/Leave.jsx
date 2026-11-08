import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// "25 Dec" for yearly holidays, "9 Apr 2026" for one-off dates.
function formatHoliday(holiday) {
    const [y, m, d] = holiday.date.split('-').map(Number);
    return holiday.recurring ? `${d} ${MONTHS[m - 1]}` : `${d} ${MONTHS[m - 1]} ${y}`;
}

// Public holidays: leave never counts these days.
function Holidays({ holidays }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        date: '',
        name: '',
        recurring: false,
    });

    const add = (e) => {
        e.preventDefault();
        post(route('settings.holidays.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const remove = (holiday) =>
        router.delete(route('settings.holidays.destroy', holiday.id), { preserveScroll: true });

    return (
        <div className="space-y-6 rounded-lg bg-white p-6 shadow">
            <div>
                <h3 className="text-lg font-medium text-content">Public holidays</h3>
                <p className="mt-1 text-sm text-muted">
                    Leave never counts weekends or these days. Example: if Tuesday is a holiday, leave
                    from Monday to Wednesday is 2 days.
                </p>
            </div>

            <form onSubmit={add} className="grid gap-3 sm:grid-cols-[auto_1fr_auto] sm:items-end">
                <div>
                    <InputLabel htmlFor="holiday_date" value="Date" />
                    <TextInput id="holiday_date" type="date" className="mt-1 block w-full" value={data.date}
                        onChange={(e) => setData('date', e.target.value)} required />
                </div>
                <div>
                    <InputLabel htmlFor="holiday_name" value="Holiday name" />
                    <TextInput id="holiday_name" className="mt-1 block w-full" value={data.name}
                        placeholder="e.g. Christmas Day"
                        onChange={(e) => setData('name', e.target.value)} required />
                </div>
                <PrimaryButton disabled={processing}>Add</PrimaryButton>
                <label className="flex items-center gap-2 text-sm text-content sm:col-span-3">
                    <input
                        type="checkbox"
                        className="rounded border-line text-accent shadow-sm focus:ring-accent"
                        checked={data.recurring}
                        onChange={(e) => setData('recurring', e.target.checked)}
                    />
                    Same date every year
                </label>
                <div className="sm:col-span-3">
                    <InputError message={errors.date} />
                    <InputError message={errors.name} />
                </div>
            </form>

            {holidays.length === 0 ? (
                <p className="rounded-md bg-bg px-4 py-3 text-sm text-muted">No holidays added yet.</p>
            ) : (
                <ul className="divide-y divide-line rounded-md border border-line">
                    {holidays.map((holiday) => (
                        <li key={holiday.id} className="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                            <div>
                                <span className="font-medium text-content">{holiday.name}</span>
                                <span className="ms-2 text-muted">{formatHoliday(holiday)}</span>
                                {holiday.recurring && (
                                    <span className="ms-2 rounded bg-accent-tint px-2 py-0.5 text-xs font-medium text-accent-text">
                                        every year
                                    </span>
                                )}
                            </div>
                            <button type="button" onClick={() => remove(holiday)}
                                className="text-xs font-medium text-danger hover:underline">
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

// HR/Admin: company leave policy. Rollover decides whether unused annual
// leave from last year is added to this year's allowance.
export default function Leave({ settings, holidays }) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        rollover_enabled: settings.rollover_enabled,
        rollover_max_days: settings.rollover_max_days,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('settings.leave.update'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<PageHeader>Leave Settings</PageHeader>}>
            <Head title="Leave Settings" />

            <div className="mx-auto max-w-2xl space-y-6 py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                    <div>
                        <h3 className="text-lg font-medium text-content">Annual leave rollover</h3>
                        <p className="mt-1 text-sm text-muted">
                            When on, an employee's unused annual leave from last year is added to this
                            year's allowance, up to the maximum below. When off, unused days are lost at
                            the end of the year.
                        </p>
                    </div>

                    <label className="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            className="h-5 w-5 rounded border-line text-accent shadow-sm focus:ring-accent"
                            checked={data.rollover_enabled}
                            onChange={(e) => setData('rollover_enabled', e.target.checked)}
                        />
                        <span className="text-sm font-medium text-content">
                            Allow unused annual leave to roll over
                        </span>
                    </label>
                    <InputError message={errors.rollover_enabled} />

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
                            onChange={(e) => setData('rollover_max_days', e.target.value)}
                            required
                        />
                        <p className="mt-1 text-xs text-muted">
                            Example: with a maximum of 5, someone who left 8 days unused last year gets 5
                            extra days this year.
                        </p>
                        <InputError message={errors.rollover_max_days} className="mt-1" />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing || !isDirty}>Save Settings</PrimaryButton>
                    </div>
                </form>

                <Holidays holidays={holidays} />
            </div>
        </AuthenticatedLayout>
    );
}
