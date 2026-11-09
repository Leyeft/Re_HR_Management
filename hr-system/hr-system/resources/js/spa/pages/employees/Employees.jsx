import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useSearchParams } from 'react-router-dom';
import Avatar from '../../components/Avatar';
import DeleteEmployeeModal from '../../components/DeleteEmployeeModal';
import EmployeeDetailsModal from '../../components/EmployeeDetailsModal';
import ExportLinks from '../../components/ExportLinks';
import Page, { LoadError } from '../../components/Page';
import Pagination from '../../components/Pagination';
import Spinner, { PageLoader } from '../../components/Spinner';
import { selectCan } from '../../store/authSlice';
import { fetchDepartments } from '../../store/departmentsSlice';
import { fetchEmployees, restoreEmployee } from '../../store/employeesSlice';
import { fetchSkills } from '../../store/skillsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

const FILTER_KEYS = ['search', 'department', 'rank', 'skill', 'sort', 'trashed', 'page'];

/**
 * Employee list (Admin/HR). Search, filters, sort and page live in the URL
 * (?search=&department=&rank=&sort=&page=) and are applied on the server
 * (server-side filtering + pagination). Back/forward and shared links keep them.
 */
export default function Employees() {
    const dispatch = useDispatch();
    const [params, setParams] = useSearchParams();
    const { items, meta, status, error } = useSelector((state) => state.employees.list);
    const departments = useSelector((state) => state.departments.items);
    const skills = useSelector((state) => state.skills.items);
    const can = useSelector(selectCan);
    const [viewing, setViewing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [restoring, setRestoring] = useState(null);

    const filters = Object.fromEntries(FILTER_KEYS.map((k) => [k, params.get(k) ?? '']));
    const [search, setSearch] = useState(filters.search);
    const query = params.toString();

    // Load the list whenever the URL's filters change.
    const load = () => dispatch(fetchEmployees(Object.fromEntries(params)));
    useEffect(() => {
        load();
    }, [query]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!departments.length) dispatch(fetchDepartments());
    }, [dispatch, departments.length]);

    useEffect(() => {
        if (!skills.length) dispatch(fetchSkills());
    }, [dispatch, skills.length]);

    const showingDeleted = filters.trashed === 'only';

    const restore = async (e) => {
        setRestoring(e.id);
        try {
            const res = await dispatch(restoreEmployee(e.id)).unwrap();
            dispatch(toastSuccess(`${e.user?.name}: ${res.message}`));
        } catch (err) {
            dispatch(toastError(err.message));
        } finally {
            setRestoring(null);
        }
    };

    // Change filters; any filter change goes back to page 1.
    const update = (changes) => {
        const next = { ...filters, page: '', ...changes };
        setParams(Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v !== null)));
    };

    const anyFilter = filters.search || filters.department || filters.rank || filters.skill;

    return (
        <Page
            title="Employees"
            actions={
                <>
                    <ExportLinks path="/reports/employees" params={{ search: filters.search, department: filters.department, rank: filters.rank, skill: filters.skill, sort: filters.sort }} />
                    <Link to="/skills" className="rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium hover:bg-bg">
                        Skills
                    </Link>
                    <Link to="/employees/new" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover">
                        + Add employee
                    </Link>
                </>
            }
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    update({ search });
                }}
                className="flex flex-col gap-3 rounded-lg bg-surface p-4 shadow md:flex-row md:items-end"
                role="search"
            >
                <label className="flex-1 text-xs font-medium text-muted">
                    Search
                    <TextInput type="search" className="mt-1 block w-full text-sm" placeholder="Name or email" value={search} onChange={(e) => setSearch(e.target.value)} />
                </label>
                <label className="text-xs font-medium text-muted">
                    Department
                    <SelectInput className="mt-1 block w-full text-sm md:w-48" value={filters.department} onChange={(e) => update({ department: e.target.value })}>
                        <option value="">All departments</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                    </SelectInput>
                </label>
                <label className="text-xs font-medium text-muted">
                    Rank
                    <SelectInput className="mt-1 block w-full text-sm md:w-40" value={filters.rank} onChange={(e) => update({ rank: e.target.value })}>
                        <option value="">All ranks</option>
                        <option value="employee">Employee</option>
                        <option value="head">Head</option>
                    </SelectInput>
                </label>
                <label className="text-xs font-medium text-muted">
                    Skill
                    <SelectInput className="mt-1 block w-full text-sm md:w-44" value={filters.skill} onChange={(e) => update({ skill: e.target.value })}>
                        <option value="">Any skill</option>
                        {skills.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                    </SelectInput>
                </label>
                <div className="flex gap-2">
                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">Search</button>
                    {anyFilter && (
                        <button
                            type="button"
                            onClick={() => {
                                setSearch('');
                                setParams({});
                            }}
                            className="rounded-md border border-line bg-surface px-4 py-2 text-sm hover:bg-bg"
                        >
                            Clear
                        </button>
                    )}
                </div>
            </form>

            {status === 'failed' && <LoadError message={error} onRetry={load} />}

            <div role="tablist" className="flex w-fit gap-1 rounded-lg bg-surface p-1 shadow">
                {[['', 'Active'], ['only', 'Deleted']].map(([value, label]) => (
                    <button
                        key={label}
                        type="button"
                        role="tab"
                        aria-selected={filters.trashed === value}
                        onClick={() => update({ trashed: value })}
                        className={`rounded-md px-3 py-1.5 text-sm font-medium ${filters.trashed === value ? 'bg-primary text-white' : 'text-muted hover:bg-bg hover:text-content'}`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            <div className="relative rounded-lg bg-surface shadow">
                {status === 'loading' && items.length > 0 && (
                    <div className="absolute right-4 top-3"><Spinner className="h-5 w-5 text-primary-text" label="Updating" /></div>
                )}

                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading employees…" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-bg text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th className="p-3">ID</th>
                                    <th className="p-3">
                                        <button
                                            type="button"
                                            onClick={() => update({ sort: filters.sort === 'desc' ? '' : 'desc' })}
                                            className="inline-flex items-center gap-1 uppercase hover:text-content"
                                            aria-label={`Sort by name ${filters.sort === 'desc' ? 'A to Z' : 'Z to A'}`}
                                        >
                                            Name {filters.sort === 'desc' ? '↓' : '↑'}
                                        </button>
                                    </th>
                                    <th className="p-3">Email</th>
                                    <th className="p-3">Department</th>
                                    <th className="p-3">Rank</th>
                                    <th className="p-3">Annual leave left</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((e) => (
                                    <tr key={e.id} className="border-t border-line">
                                        <td className="p-3 font-mono text-muted">{e.employee_code}</td>
                                        <td className="p-3">
                                            <button type="button" onClick={() => setViewing(e)} className="inline-flex items-center gap-2 font-medium text-primary-text hover:underline">
                                                <Avatar user={e.user} size="xs" />
                                                {e.user?.name}
                                            </button>
                                        </td>
                                        <td className="p-3 text-muted">{e.user?.email}</td>
                                        <td className="p-3">{e.department?.name ?? '—'}</td>
                                        <td className="p-3">
                                            <span className={`rounded px-2 py-1 text-xs font-semibold ${e.rank === 'head' ? 'bg-primary text-white' : 'bg-accent-tint text-accent-text'}`}>
                                                {e.rank === 'head' ? 'Head' : 'Employee'}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {e.leave_summary ? (
                                                <>
                                                    <span className="font-medium tabular-nums text-content">{e.leave_summary.annual.remaining}/{e.leave_summary.annual.total}</span>
                                                    <span className="ms-1 text-xs text-muted">({e.leave_summary.tier})</span>
                                                </>
                                            ) : '—'}
                                        </td>
                                        <td className="whitespace-nowrap p-3 text-right">
                                            {showingDeleted ? (
                                                <button type="button" onClick={() => restore(e)} disabled={restoring === e.id} className="font-medium text-primary-text hover:underline disabled:opacity-50">
                                                    {restoring === e.id ? 'Restoring…' : 'Restore'}
                                                </button>
                                            ) : (
                                                e.user?.role === 'admin' && !can.manage_admins ? (
                                                    <span className="text-xs text-muted" title="Only an Admin can change Admin accounts">Admin account</span>
                                                ) : (
                                                    <>
                                                        <Link to={`/employees/${e.id}/edit`} className="me-4 text-primary-text hover:underline">Edit</Link>
                                                        <button type="button" onClick={() => setDeleting(e)} className="text-danger hover:underline">Delete</button>
                                                    </>
                                                )
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {status === 'succeeded' && items.length === 0 && (
                                    <tr>
                                        <td colSpan="7" className="p-6 text-center text-muted">
                                            {showingDeleted ? 'No deleted employees.' : anyFilter ? 'No employees match these filters.' : 'No employees yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={meta} onPage={(page) => update({ page: String(page) })} label="employees" />
                </div>
            </div>

            <EmployeeDetailsModal employee={viewing} onClose={() => setViewing(null)} />
            <DeleteEmployeeModal employee={deleting} onClose={() => setDeleting(null)} onDeleted={load} />
        </Page>
    );
}
