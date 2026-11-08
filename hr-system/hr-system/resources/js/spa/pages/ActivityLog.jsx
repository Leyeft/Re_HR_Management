// Activity log page (Admin/HR): the audit trail of who changed what, with filters in the URL.
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useSearchParams } from 'react-router-dom';
import Page, { LoadError } from '../components/Page';
import Pagination from '../components/Pagination';
import Spinner, { PageLoader } from '../components/Spinner';
import { fetchActivity } from '../store/activitySlice';
import { formatDateTime } from '../utils/time';

const FILTERS = ['search', 'action', 'subject_type', 'from', 'to'];
const EMPTY = Object.fromEntries(FILTERS.map((k) => [k, '']));

const ACTION_STYLE = {
    created: 'bg-success-bg text-success',
    updated: 'bg-info-bg text-info',
    deleted: 'bg-danger-bg text-danger',
    restored: 'bg-warning-bg text-warning',
    login: 'bg-bg text-muted',
};

const SUBJECT_LABELS = {
    Employee: 'Employee',
    User: 'User account',
    Department: 'Department',
    LeaveRequest: 'Leave request',
    Skill: 'Skill',
    Holiday: 'Holiday',
    Setting: 'Setting',
};

/**
 * /activity — audit trail (Admin/HR): who created, changed, deleted or restored
 * what, and every sign-in. Filters live in the URL.
 */
export default function ActivityLog() {
    const dispatch = useDispatch();
    const [params, setParams] = useSearchParams();
    const { items, meta, status, error } = useSelector((state) => state.activity);
    const applied = { ...EMPTY, ...Object.fromEntries([...params].filter(([k]) => FILTERS.includes(k))) };
    const [values, setValues] = useState(applied);
    const query = params.toString();

    const load = () => dispatch(fetchActivity(Object.fromEntries(params)));
    useEffect(() => {
        load();
        setValues({ ...EMPTY, ...Object.fromEntries([...params].filter(([k]) => FILTERS.includes(k))) });
    }, [query]); // eslint-disable-line react-hooks/exhaustive-deps

    const set = (key) => (e) => setValues((v) => ({ ...v, [key]: e.target.value }));
    const apply = (next) => setParams(Object.fromEntries(Object.entries(next).filter(([, v]) => v)));
    const anyFilter = FILTERS.some((k) => applied[k]);

    const field = 'mt-1 block w-full py-1.5 text-sm';
    const label = 'block text-xs font-medium text-muted';

    return (
        <Page title="Activity log">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply(values);
                }}
                className="grid gap-3 rounded-lg bg-white p-4 shadow sm:grid-cols-2 lg:grid-cols-6"
                role="search"
            >
                <label className={`${label} lg:col-span-2`}>
                    Search
                    <TextInput type="search" className={field} value={values.search} onChange={set('search')} placeholder="e.g. Bobby, Finance" />
                </label>
                <label className={label}>
                    Action
                    <SelectInput className={field} value={values.action} onChange={set('action')}>
                        <option value="">All</option>
                        {Object.keys(ACTION_STYLE).map((a) => (
                            <option key={a} value={a}>{a === 'login' ? 'Sign-in' : a[0].toUpperCase() + a.slice(1)}</option>
                        ))}
                    </SelectInput>
                </label>
                <label className={label}>
                    Record
                    <SelectInput className={field} value={values.subject_type} onChange={set('subject_type')}>
                        <option value="">All</option>
                        {Object.entries(SUBJECT_LABELS).map(([value, text]) => (
                            <option key={value} value={value}>{text}</option>
                        ))}
                    </SelectInput>
                </label>
                <label className={label}>
                    From
                    <TextInput type="date" className={field} value={values.from} onChange={set('from')} />
                </label>
                <label className={label}>
                    To
                    <TextInput type="date" className={field} value={values.to} min={values.from || undefined} onChange={set('to')} />
                </label>
                <div className="flex gap-2 sm:col-span-2 lg:col-span-6 lg:justify-end">
                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">Apply</button>
                    {anyFilter && (
                        <button type="button" onClick={() => setParams({})} className="rounded-md border border-line bg-white px-4 py-2 text-sm hover:bg-bg">Reset</button>
                    )}
                </div>
            </form>

            {status === 'failed' && <LoadError message={error} onRetry={load} />}

            <section className="relative rounded-lg bg-white shadow">
                {status === 'loading' && items.length > 0 && (
                    <div className="absolute right-4 top-3"><Spinner className="h-5 w-5 text-primary" label="Updating" /></div>
                )}
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading activity…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">When</th>
                                    <th className="p-3">Who</th>
                                    <th className="p-3">Action</th>
                                    <th className="p-3">What</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((log) => (
                                    <tr key={log.id} className="border-t border-line align-top">
                                        <td className="whitespace-nowrap p-3 text-muted">{formatDateTime(log.created_at)}</td>
                                        <td className="whitespace-nowrap p-3">{log.user?.name ?? <span className="text-muted">System</span>}</td>
                                        <td className="p-3">
                                            <span className={`rounded px-2 py-0.5 text-xs font-medium ${ACTION_STYLE[log.action] ?? 'bg-bg text-muted'}`}>
                                                {log.action === 'login' ? 'sign-in' : log.action}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <p className="text-content">{log.description}</p>
                                            {log.changes && <Changes changes={log.changes} />}
                                        </td>
                                    </tr>
                                ))}
                                {status === 'succeeded' && !items.length && (
                                    <tr><td colSpan="4" className="p-6 text-center text-muted">{anyFilter ? 'No activity matches these filters.' : 'No activity yet.'}</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={meta} onPage={(page) => setParams({ ...Object.fromEntries(params), page: String(page) })} label="entries" />
                </div>
            </section>
        </Page>
    );
}

// Old → new value for each changed field.
function Changes({ changes }) {
    const show = (v) => (v === null || v === undefined || v === '' ? '—' : String(v));

    return (
        <details className="mt-1">
            <summary className="cursor-pointer text-xs text-muted hover:text-content">
                {Object.keys(changes).length} change{Object.keys(changes).length === 1 ? '' : 's'}
            </summary>
            <dl className="mt-2 space-y-1 rounded-md bg-bg p-2 text-xs">
                {Object.entries(changes).map(([field, { old, new: next }]) => (
                    <div key={field} className="flex flex-wrap gap-x-2">
                        <dt className="font-medium capitalize text-content">{field.replaceAll('_', ' ')}:</dt>
                        <dd className="text-muted">
                            <span className="line-through">{show(old)}</span> → <span className="text-content">{show(next)}</span>
                        </dd>
                    </div>
                ))}
            </dl>
        </details>
    );
}
