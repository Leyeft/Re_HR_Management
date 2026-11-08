import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { tierFromYears, yearsSince } from '@/Utils/leave';
import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate, useParams } from 'react-router-dom';
import Page, { LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import { fetchDepartments } from '../../store/departmentsSlice';
import { createEmployee, fetchEmployee, updateEmployee } from '../../store/employeesSlice';
import { toastSuccess } from '../../store/toastSlice';

const today = new Date().toISOString().slice(0, 10);

/**
 * /employees/new and /employees/:id/edit — one form for both.
 * On edit, the employee is loaded first and the form starts with their details.
 */
export default function EmployeeForm() {
    const { id } = useParams();
    const dispatch = useDispatch();
    const { item, status, error } = useSelector((state) => state.employees.current);
    const departments = useSelector((state) => state.departments.items);

    useEffect(() => {
        if (!departments.length) dispatch(fetchDepartments());
        if (id) dispatch(fetchEmployee(id));
    }, [dispatch, id, departments.length]);

    if (id && status === 'failed') {
        return (
            <Page title="Edit employee" width="max-w-3xl">
                <LoadError message={error} onRetry={() => dispatch(fetchEmployee(id))} />
            </Page>
        );
    }

    if (id && (!item || item.id !== Number(id))) {
        return (
            <Page title="Edit employee" width="max-w-3xl">
                <PageLoader label="Loading employee…" />
            </Page>
        );
    }

    // `key` makes the form start fresh when switching between people.
    return <Form key={id ?? 'new'} employee={id ? item : null} departments={departments} />;
}

function Form({ employee, departments }) {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const isNew = !employee;

    const form = useApiForm({
        name: employee?.user?.name ?? '',
        email: employee?.user?.email ?? '',
        password: '',
        role: employee?.user?.role ?? 'employee',
        department_id: employee?.department_id ?? '',
        rank: employee?.rank ?? 'employee',
        date_of_birth: employee?.date_of_birth ?? '',
        date_hired: employee?.date_hired ?? '',
    });
    const { data, errors, field } = form;

    // HR and Admin accounts can't be Heads — lock the rank when one is chosen.
    const rankLocked = data.role !== 'employee';
    useEffect(() => {
        if (rankLocked && data.rank !== 'employee') form.setData('rank', 'employee');
    }, [rankLocked]); // eslint-disable-line react-hooks/exhaustive-deps

    const tier = data.date_hired ? tierFromYears(yearsSince(data.date_hired)) : null;

    const submit = (e) => {
        e.preventDefault();
        const { password, ...rest } = data;
        form.submit(
            (values) => dispatch(isNew ? createEmployee(values) : updateEmployee({ id: employee.id, ...rest })).unwrap(),
            {
                onSuccess: (res) => {
                    dispatch(toastSuccess(res.message));
                    navigate(`/employees/${res.data.id}`);
                },
            },
        );
    };

    return (
        <Page title={isNew ? 'Add employee' : `Edit ${employee.user?.name}`} width="max-w-3xl">
            <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow" noValidate>
                {isNew ? (
                    <p className="rounded-md bg-bg px-4 py-2 text-xs text-muted">
                        An Employee ID (e.g. 100001) is assigned automatically from the chosen department.
                    </p>
                ) : (
                    <p className="rounded-md bg-bg px-4 py-2 text-sm text-muted">
                        Employee ID: <span className="font-mono font-medium text-content">{employee.employee_code}</span>
                    </p>
                )}

                <fieldset className="grid gap-4 sm:grid-cols-2">
                    <legend className="mb-2 text-sm font-semibold text-content">Personal details</legend>
                    <Field id="name" label="Full name" error={errors.name}>
                        <TextInput id="name" className="mt-1 block w-full" value={data.name} onChange={field('name')} required autoComplete="off" />
                    </Field>
                    <Field id="email" label="Work email" error={errors.email}>
                        <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email} onChange={field('email')} required autoComplete="off" />
                    </Field>
                    {isNew && (
                        <Field id="password" label="Temporary password" error={errors.password} hint="At least 8 characters. They can change it from their profile.">
                            <TextInput id="password" type="password" className="mt-1 block w-full" value={data.password} onChange={field('password')} required autoComplete="new-password" />
                        </Field>
                    )}
                    <Field id="date_of_birth" label="Date of birth" error={errors.date_of_birth}>
                        <TextInput id="date_of_birth" type="date" max={today} className="mt-1 block w-full" value={data.date_of_birth} onChange={field('date_of_birth')} required />
                    </Field>
                </fieldset>

                <fieldset className="grid gap-4 sm:grid-cols-2">
                    <legend className="mb-2 text-sm font-semibold text-content">Job</legend>
                    <Field id="department_id" label="Department" error={errors.department_id}>
                        <SelectInput id="department_id" className="mt-1 block w-full" value={data.department_id} onChange={field('department_id')} required>
                            <option value="">Choose a department</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </SelectInput>
                    </Field>
                    <Field
                        id="date_hired"
                        label="Date hired"
                        error={errors.date_hired}
                        hint={tier ? `Leave allowance: ${tier.label} · ${tier.days} days/year` : 'Sets the yearly leave allowance.'}
                    >
                        <TextInput id="date_hired" type="date" max={today} className="mt-1 block w-full" value={data.date_hired} onChange={field('date_hired')} required />
                    </Field>
                    <Field id="role" label="System role" error={errors.role} hint="What they can do in the app.">
                        <SelectInput id="role" className="mt-1 block w-full" value={data.role} onChange={field('role')}>
                            <option value="employee">Employee — standard access</option>
                            <option value="hr">HR — manages staff and signs off leave</option>
                            <option value="admin">Admin — full access</option>
                        </SelectInput>
                    </Field>
                    <Field
                        id="rank"
                        label="Rank"
                        error={errors.rank}
                        hint={rankLocked ? 'HR and Admin accounts can’t also be a Head of Department.' : 'A Head approves leave for their department.'}
                    >
                        <SelectInput id="rank" className="mt-1 block w-full disabled:bg-bg disabled:text-muted" value={data.rank} onChange={field('rank')} disabled={rankLocked}>
                            <option value="employee">Employee</option>
                            <option value="head">Head of Department</option>
                        </SelectInput>
                    </Field>
                </fieldset>

                <div className="flex justify-end gap-3 border-t border-line pt-4">
                    <Link to={isNew ? '/employees' : `/employees/${employee.id}`} className="rounded-md border border-line bg-white px-4 py-2 text-sm hover:bg-bg">
                        Cancel
                    </Link>
                    <PrimaryButton disabled={form.processing}>{form.processing ? 'Saving…' : isNew ? 'Create employee' : 'Save changes'}</PrimaryButton>
                </div>
            </form>
        </Page>
    );
}

function Field({ id, label, error, hint, children }) {
    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            {children}
            {hint && !error && <p className="mt-1 text-xs text-muted">{hint}</p>}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
