import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import Page, { LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import { createDepartment, deleteDepartment, fetchDepartments, updateDepartment } from '../../store/departmentsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

/**
 * Departments (Admin/HR). The full list is loaded once and the search box filters it
 * instantly in the browser (client-side filtering). Create and edit open in a modal.
 */
export default function Departments() {
    const dispatch = useDispatch();
    const { items, status, error } = useSelector((state) => state.departments);
    const [search, setSearch] = useState('');
    const [editing, setEditing] = useState(null); // null = closed, {} = new, {…} = existing
    const [deleting, setDeleting] = useState(null);

    useEffect(() => {
        dispatch(fetchDepartments());
    }, [dispatch]);

    // Client-side filter: name or description contains the search text.
    const shown = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return items;
        return items.filter((d) => d.name.toLowerCase().includes(q) || (d.description ?? '').toLowerCase().includes(q));
    }, [items, search]);

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
                    <label className="w-full sm:w-72">
                        <span className="sr-only">Search departments</span>
                        <TextInput
                            type="search"
                            className="block w-full text-sm"
                            placeholder="Search departments…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </label>
                    <p className="text-sm text-muted" aria-live="polite">
                        {shown.length} of {items.length} department{items.length === 1 ? '' : 's'}
                    </p>
                </div>

                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading departments…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">Name</th>
                                    <th className="p-3">Description</th>
                                    <th className="p-3 text-right">Employees</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {shown.map((d) => (
                                    <tr key={d.id} className="border-t border-line">
                                        <td className="p-3 font-medium text-content">{d.name}</td>
                                        <td className="p-3 text-muted">{d.description || '—'}</td>
                                        <td className="p-3 text-right tabular-nums">
                                            <Link to={`/employees?department=${d.id}`} className="text-accent-text hover:underline">
                                                {d.employees_count}
                                            </Link>
                                        </td>
                                        <td className="whitespace-nowrap p-3 text-right">
                                            <button type="button" onClick={() => setEditing(d)} className="me-4 text-primary hover:underline">
                                                Edit
                                            </button>
                                            <button type="button" onClick={() => setDeleting(d)} className="text-danger hover:underline">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {shown.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="p-6 text-center text-muted">
                                            {items.length ? 'No departments match your search.' : 'No departments yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {editing && <DepartmentModal department={editing} onClose={() => setEditing(null)} />}
            <DeleteDepartmentModal department={deleting} onClose={() => setDeleting(null)} />
        </Page>
    );
}

// Create (no id) or edit (with id) in a modal.
function DepartmentModal({ department, onClose }) {
    const dispatch = useDispatch();
    const isNew = !department.id;
    const form = useApiForm({ name: department.name ?? '', description: department.description ?? '' });

    const submit = (e) => {
        e.preventDefault();
        form.submit(
            (data) => dispatch(isNew ? createDepartment(data) : updateDepartment({ id: department.id, ...data })).unwrap(),
            {
                onSuccess: (res) => {
                    dispatch(toastSuccess(res.message ?? 'Saved.'));
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
                    <p className="mt-2 text-sm text-muted">This department has no employees. This cannot be undone.</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={onClose}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirm} disabled={busy}>{busy ? 'Deleting…' : 'Delete'}</DangerButton>
                    </div>
                </div>
            )}
        </Modal>
    );
}
