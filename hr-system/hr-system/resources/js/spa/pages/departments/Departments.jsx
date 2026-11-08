import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import Page, { LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import {
    createDepartment,
    deleteDepartment,
    fetchDeletedDepartments,
    fetchDepartments,
    restoreDepartment,
    updateDepartment,
} from '../../store/departmentsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

/**
 * Departments (Admin/HR).
 * - Shown as a tree: sub-departments sit indented under their parent (parent_id).
 * - The search box filters the loaded list instantly in the browser (client-side filtering).
 * - Create/edit in a modal; delete is a soft delete with a Deleted tab to restore.
 */
export default function Departments() {
    const dispatch = useDispatch();
    const { items, status, error, deleted } = useSelector((state) => state.departments);
    const [tab, setTab] = useState('active');
    const [search, setSearch] = useState('');
    const [editing, setEditing] = useState(null); // null = closed, {} = new, {…} = existing
    const [deleting, setDeleting] = useState(null);

    useEffect(() => {
        dispatch(fetchDepartments());
    }, [dispatch]);

    useEffect(() => {
        if (tab === 'deleted') dispatch(fetchDeletedDepartments());
    }, [dispatch, tab]);

    // Tree order: each department followed by its sub-departments, with a depth for indenting.
    const tree = useMemo(() => toTree(items), [items]);

    // Client-side filter: when searching, show matching departments as a flat list.
    const q = search.trim().toLowerCase();
    const shown = q
        ? items.filter((d) => d.name.toLowerCase().includes(q) || (d.description ?? '').toLowerCase().includes(q)).map((d) => ({ ...d, depth: 0 }))
        : tree;

    return (
        <Page
            title="Departments"
            actions={
                <PrimaryButton type="button" onClick={() => setEditing({})}>
                    + Add department
                </PrimaryButton>
            }
        >
            {status === 'failed' && <LoadError message={error} onRetry={() => dispatch(fetchDepartments())} />}

            <div className="rounded-lg bg-white p-6 shadow">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <Tabs value={tab} onChange={setTab} deletedCount={deleted.status === 'succeeded' ? deleted.items.length : null} />
                    {tab === 'active' && (
                        <div className="flex flex-wrap items-center gap-3">
                            <label className="w-full sm:w-64">
                                <span className="sr-only">Search departments</span>
                                <TextInput type="search" className="block w-full text-sm" placeholder="Search departments…" value={search} onChange={(e) => setSearch(e.target.value)} />
                            </label>
                            <p className="text-sm text-muted" aria-live="polite">
                                {shown.length} of {items.length}
                            </p>
                        </div>
                    )}
                </div>

                {tab === 'active' ? (
                    status === 'loading' && !items.length ? (
                        <PageLoader label="Loading departments…" />
                    ) : (
                        <ActiveTable rows={shown} searching={!!q} total={items.length} onEdit={setEditing} onDelete={setDeleting} />
                    )
                ) : (
                    <DeletedTable />
                )}
            </div>

            {editing && <DepartmentModal department={editing} all={items} onClose={() => setEditing(null)} />}
            <DeleteDepartmentModal department={deleting} onClose={() => setDeleting(null)} />
        </Page>
    );
}

function Tabs({ value, onChange, deletedCount }) {
    const tab = (key, label) => (
        <button
            type="button"
            role="tab"
            aria-selected={value === key}
            onClick={() => onChange(key)}
            className={`rounded-md px-3 py-1.5 text-sm font-medium ${value === key ? 'bg-primary text-white' : 'text-muted hover:bg-bg hover:text-content'}`}
        >
            {label}
        </button>
    );

    return (
        <div role="tablist" className="flex gap-1 rounded-lg bg-bg p-1">
            {tab('active', 'Active')}
            {tab('deleted', `Deleted${deletedCount ? ` (${deletedCount})` : ''}`)}
        </div>
    );
}

function ActiveTable({ rows, searching, total, onEdit, onDelete }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th className="p-3">Name</th>
                        {searching && <th className="p-3">Part of</th>}
                        <th className="p-3">Description</th>
                        <th className="p-3 text-right">Employees</th>
                        <th className="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((d) => (
                        <tr key={d.id} className="border-t border-line">
                            <td className="p-3 font-medium text-content">
                                <span style={{ paddingInlineStart: `${d.depth * 1.5}rem` }} className="inline-flex items-center gap-2">
                                    {d.depth > 0 && <span className="text-muted" aria-hidden="true">└</span>}
                                    {d.name}
                                    {d.children_count > 0 && (
                                        <span className="rounded bg-accent-tint px-1.5 py-0.5 text-xs font-normal text-accent-text">
                                            {d.children_count} sub
                                        </span>
                                    )}
                                </span>
                            </td>
                            {searching && <td className="p-3 text-muted">{d.parent?.name ?? '—'}</td>}
                            <td className="p-3 text-muted">{d.description || '—'}</td>
                            <td className="p-3 text-right tabular-nums">
                                <Link to={`/employees?department=${d.id}`} className="text-accent-text hover:underline">{d.employees_count}</Link>
                            </td>
                            <td className="whitespace-nowrap p-3 text-right">
                                <button type="button" onClick={() => onEdit(d)} className="me-4 text-primary hover:underline">Edit</button>
                                <button type="button" onClick={() => onDelete(d)} className="text-danger hover:underline">Delete</button>
                            </td>
                        </tr>
                    ))}
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={searching ? 5 : 4} className="p-6 text-center text-muted">
                                {total ? 'No departments match your search.' : 'No departments yet.'}
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}

function DeletedTable() {
    const dispatch = useDispatch();
    const { items, status } = useSelector((state) => state.departments.deleted);
    const [busy, setBusy] = useState(null);

    const restore = async (d) => {
        setBusy(d.id);
        try {
            const res = await dispatch(restoreDepartment(d.id)).unwrap();
            dispatch(toastSuccess(`${d.name}: ${res.message}`));
            dispatch(fetchDepartments()); // counts and tree may change
        } catch (e) {
            dispatch(toastError(e.message));
        } finally {
            setBusy(null);
        }
    };

    if (status === 'loading' && !items.length) return <PageLoader label="Loading deleted departments…" />;

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th className="p-3">Name</th>
                        <th className="p-3">Deleted on</th>
                        <th className="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((d) => (
                        <tr key={d.id} className="border-t border-line">
                            <td className="p-3 font-medium text-content">{d.name}</td>
                            <td className="p-3 text-muted">{new Date(d.deleted_at).toLocaleDateString()}</td>
                            <td className="p-3 text-right">
                                <button type="button" onClick={() => restore(d)} disabled={busy === d.id} className="font-medium text-primary hover:underline disabled:opacity-50">
                                    {busy === d.id ? 'Restoring…' : 'Restore'}
                                </button>
                            </td>
                        </tr>
                    ))}
                    {status === 'succeeded' && !items.length && (
                        <tr><td colSpan="3" className="p-6 text-center text-muted">No deleted departments.</td></tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}

// Create (no id) or edit (with id) in a modal, including the parent department.
function DepartmentModal({ department, all, onClose }) {
    const dispatch = useDispatch();
    const isNew = !department.id;
    const form = useApiForm({
        name: department.name ?? '',
        description: department.description ?? '',
        parent_id: department.parent_id ?? '',
    });

    // Can't choose itself or one of its own sub-departments as the parent (would loop).
    const blocked = useMemo(() => (isNew ? new Set() : new Set([department.id, ...descendantIds(all, department.id)])), [all, department.id, isNew]);
    const parentOptions = toTree(all).filter((d) => !blocked.has(d.id));

    const submit = (e) => {
        e.preventDefault();
        const values = { ...form.data, parent_id: form.data.parent_id || null };
        form.submit(
            () => dispatch(isNew ? createDepartment(values) : updateDepartment({ id: department.id, ...values })).unwrap(),
            {
                onSuccess: (res) => {
                    dispatch(toastSuccess(res.message ?? 'Saved.'));
                    dispatch(fetchDepartments()); // refresh sub-department counts
                    onClose();
                },
            },
        );
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="space-y-4 p-6" noValidate>
                <h2 className="text-lg font-medium text-content">{isNew ? 'Add department' : `Edit ${department.name}`}</h2>

                <div>
                    <InputLabel htmlFor="dept-name" value="Name" />
                    <TextInput id="dept-name" className="mt-1 block w-full" value={form.data.name} onChange={form.field('name')} isFocused required maxLength={255} />
                    <InputError message={form.errors.name} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="dept-parent" value="Part of (optional)" />
                    <SelectInput id="dept-parent" className="mt-1 block w-full" value={form.data.parent_id} onChange={form.field('parent_id')}>
                        <option value="">— Top level —</option>
                        {parentOptions.map((d) => (
                            <option key={d.id} value={d.id}>
                                {'  '.repeat(d.depth)}{d.depth ? '└ ' : ''}{d.name}
                            </option>
                        ))}
                    </SelectInput>
                    <p className="mt-1 text-xs text-muted">Put this department inside a larger one, e.g. Housekeeping → Operations.</p>
                    <InputError message={form.errors.parent_id} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="dept-description" value="Description (optional)" />
                    <textarea
                        id="dept-description"
                        rows="3"
                        className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                        value={form.data.description}
                        onChange={form.field('description')}
                        maxLength={1000}
                    />
                    <InputError message={form.errors.description} className="mt-1" />
                </div>

                <div className="flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>Cancel</SecondaryButton>
                    <PrimaryButton disabled={form.processing}>{form.processing ? 'Saving…' : isNew ? 'Create' : 'Save'}</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

// Delete confirmation — or a blocker if people still work there.
function DeleteDepartmentModal({ department, onClose }) {
    const dispatch = useDispatch();
    const [busy, setBusy] = useState(false);
    const hasEmployees = (department?.employees_count ?? 0) > 0;

    const confirm = async () => {
        setBusy(true);
        try {
            const res = await dispatch(deleteDepartment(department.id)).unwrap();
            dispatch(toastSuccess(res.message));
            dispatch(fetchDepartments()); // sub-departments moved up
            onClose();
        } catch (error) {
            dispatch(toastError(error.message));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Modal show={!!department} onClose={onClose} maxWidth="md">
            {hasEmployees ? (
                <div className="p-6">
                    <h2 className="text-lg font-medium text-content">🏰 Halt! The castle still has residents.</h2>
                    <p className="mt-3 text-sm text-muted">
                        Thou wouldst demolish <span className="font-medium text-content">{department?.name}</span> with{' '}
                        {department?.employees_count} employee{department?.employees_count === 1 ? '' : 's'} still inside?
                        Even the cruelest king evacuates first. Move them elsewhere, then return with thy wrecking ram.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={onClose}>Lower my sword</SecondaryButton>
                        <Link
                            to={`/employees?department=${department?.id}`}
                            className="inline-flex items-center rounded-md bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                        >
                            Evacuate the castle
                        </Link>
                    </div>
                </div>
            ) : (
                <div className="p-6">
                    <h2 className="text-lg font-medium text-content">Delete {department?.name}?</h2>
                    <p className="mt-2 text-sm text-muted">
                        It has no employees.
                        {department?.children_count > 0 && ` Its ${department.children_count} sub-department(s) will move up one level.`}{' '}
                        You can restore it later from the Deleted tab.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={onClose}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirm} disabled={busy}>{busy ? 'Deleting…' : 'Delete'}</DangerButton>
                    </div>
                </div>
            )}
        </Modal>
    );
}

/* ----- Tree helpers (departments.parent_id) ----- */

// Departments in tree order (parent, then its children, …), each with a `depth`.
function toTree(items) {
    const ids = new Set(items.map((d) => d.id));
    const children = new Map();
    for (const d of items) {
        const key = d.parent_id && ids.has(d.parent_id) ? d.parent_id : null;
        if (!children.has(key)) children.set(key, []);
        children.get(key).push(d);
    }

    const out = [];
    const seen = new Set();
    const walk = (parentId, depth) => {
        for (const d of (children.get(parentId) ?? []).sort((a, b) => a.name.localeCompare(b.name))) {
            if (seen.has(d.id)) continue; // never loop on bad data
            seen.add(d.id);
            out.push({ ...d, depth });
            walk(d.id, depth + 1);
        }
    };
    walk(null, 0);
    return out;
}

// IDs of every department below `id`.
function descendantIds(items, id) {
    const result = [];
    let level = [id];
    while (level.length) {
        level = items.filter((d) => level.includes(d.parent_id) && !result.includes(d.id)).map((d) => d.id);
        result.push(...level);
    }
    return result;
}
