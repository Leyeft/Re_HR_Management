import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import Page, { Card, LoadError } from '../../components/Page';
import { PageLoader } from '../../components/Spinner';
import useApiForm from '../../hooks/useApiForm';
import { createSkill, deleteSkill, fetchSkills, updateSkill } from '../../store/skillsSlice';
import { toastError, toastSuccess } from '../../store/toastSlice';

/**
 * /skills (Admin/HR) — the list of skills employees can have. Each person's skills and
 * levels are set on their Edit page (stored in the employee_skill pivot table).
 */
export default function Skills() {
    const dispatch = useDispatch();
    const { items, status, error } = useSelector((state) => state.skills);
    const form = useApiForm({ name: '' });

    useEffect(() => {
        dispatch(fetchSkills());
    }, [dispatch]);

    const add = (e) => {
        e.preventDefault();
        form.submit((data) => dispatch(createSkill(data)).unwrap(), {
            onSuccess: (res) => {
                dispatch(toastSuccess(res.message));
                form.reset({ name: '' });
            },
        });
    };

    return (
        <Page title="Skills" width="max-w-3xl" actions={<Link to="/employees" className="text-sm text-muted hover:text-content">← Employees</Link>}>
            <Card title="Add a skill" description="Then give it to people (with a level) on their Edit page.">
                <form onSubmit={add} className="flex flex-col gap-2 sm:flex-row sm:items-start" noValidate>
                    <div className="flex-1">
                        <label htmlFor="skill-name" className="sr-only">Skill name</label>
                        <TextInput id="skill-name" className="block w-full" placeholder="e.g. First aid" maxLength={100} value={form.data.name} onChange={form.field('name')} />
                        <InputError message={form.errors.name} className="mt-1" />
                    </div>
                    <PrimaryButton disabled={form.processing || !form.data.name.trim()}>{form.processing ? 'Adding…' : 'Add'}</PrimaryButton>
                </form>
            </Card>

            {status === 'failed' && <LoadError message={error} onRetry={() => dispatch(fetchSkills())} />}

            <section className="rounded-lg bg-white shadow">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading skills…" />
                ) : items.length === 0 ? (
                    <p className="p-6 text-center text-sm text-muted">No skills yet.</p>
                ) : (
                    <ul className="divide-y divide-line">
                        {items.map((skill) => (
                            <SkillRow key={skill.id} skill={skill} />
                        ))}
                    </ul>
                )}
            </section>
        </Page>
    );
}

function SkillRow({ skill }) {
    const dispatch = useDispatch();
    const [editing, setEditing] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const form = useApiForm({ name: skill.name });

    const save = (e) => {
        e.preventDefault();
        form.submit((data) => dispatch(updateSkill({ id: skill.id, ...data })).unwrap(), {
            onSuccess: (res) => {
                dispatch(toastSuccess(res.message));
                setEditing(false);
            },
        });
    };

    const remove = async () => {
        try {
            const res = await dispatch(deleteSkill(skill.id)).unwrap();
            dispatch(toastSuccess(`${skill.name}: ${res.message}`));
        } catch (e) {
            dispatch(toastError(e.message));
        }
    };

    if (editing) {
        return (
            <li className="p-4">
                <form onSubmit={save} className="flex flex-col gap-2 sm:flex-row sm:items-start" noValidate>
                    <div className="flex-1">
                        <label htmlFor={`skill-${skill.id}`} className="sr-only">Skill name</label>
                        <TextInput id={`skill-${skill.id}`} className="block w-full text-sm" value={form.data.name} onChange={form.field('name')} maxLength={100} isFocused />
                        <InputError message={form.errors.name} className="mt-1" />
                    </div>
                    <div className="flex gap-2">
                        <PrimaryButton disabled={form.processing}>Save</PrimaryButton>
                        <button type="button" onClick={() => setEditing(false)} className="rounded-md border border-line px-3 py-2 text-sm hover:bg-bg">Cancel</button>
                    </div>
                </form>
            </li>
        );
    }

    return (
        <li className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
            <div>
                <span className="font-medium text-content">{skill.name}</span>
                <Link to={`/employees?skill=${skill.id}`} className="ms-3 text-accent-text hover:underline">
                    {skill.employees_count} {skill.employees_count === 1 ? 'person' : 'people'}
                </Link>
            </div>
            {confirming ? (
                <div className="flex items-center gap-3">
                    <span className="text-muted">
                        Delete{skill.employees_count ? ` and remove from ${skill.employees_count} ${skill.employees_count === 1 ? 'person' : 'people'}` : ''}?
                    </span>
                    <button type="button" onClick={remove} className="font-medium text-danger hover:underline">Yes, delete</button>
                    <button type="button" onClick={() => setConfirming(false)} className="text-muted hover:underline">No</button>
                </div>
            ) : (
                <div className="flex gap-4">
                    <button type="button" onClick={() => setEditing(true)} className="text-primary hover:underline">Rename</button>
                    <button type="button" onClick={() => setConfirming(true)} className="text-danger hover:underline">Delete</button>
                </div>
            )}
        </li>
    );
}
