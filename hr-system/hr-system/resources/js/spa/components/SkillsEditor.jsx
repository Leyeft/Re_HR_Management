import SelectInput from '@/Components/SelectInput';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { SKILL_LEVELS } from '../store/skillsSlice';

/**
 * Edit an employee's skills: value = [{ id, level }]. Saved to the employee_skill
 * pivot table when the employee form is submitted.
 */
export default function SkillsEditor({ value, onChange, allSkills, errors = {} }) {
    const [adding, setAdding] = useState('');
    const names = Object.fromEntries(allSkills.map((s) => [s.id, s.name]));
    const available = allSkills.filter((s) => !value.some((v) => v.id === s.id));

    const add = () => {
        if (!adding) return;
        onChange([...value, { id: Number(adding), level: 'intermediate' }]);
        setAdding('');
    };
    const setLevel = (id, level) => onChange(value.map((v) => (v.id === id ? { ...v, level } : v)));
    const remove = (id) => onChange(value.filter((v) => v.id !== id));

    // First error under any skills.* key (e.g. "skills.0.level").
    const error = Object.entries(errors).find(([key]) => key.startsWith('skills'))?.[1];

    return (
        <div>
            {value.length > 0 ? (
                <ul className="divide-y divide-line rounded-md border border-line">
                    {value.map((v) => (
                        <li key={v.id} className="flex flex-wrap items-center justify-between gap-3 px-3 py-2">
                            <span className="text-sm font-medium text-content">{names[v.id] ?? 'Unknown skill'}</span>
                            <div className="flex items-center gap-3">
                                <label className="sr-only" htmlFor={`level-${v.id}`}>Level for {names[v.id]}</label>
                                <SelectInput id={`level-${v.id}`} className="py-1 text-sm" value={v.level} onChange={(e) => setLevel(v.id, e.target.value)}>
                                    {SKILL_LEVELS.map((l) => (
                                        <option key={l.value} value={l.value}>{l.label}</option>
                                    ))}
                                </SelectInput>
                                <button type="button" onClick={() => remove(v.id)} className="text-sm text-danger hover:underline" aria-label={`Remove ${names[v.id]}`}>
                                    Remove
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="rounded-md bg-bg px-3 py-2 text-sm text-muted">No skills added.</p>
            )}

            {allSkills.length === 0 ? (
                <p className="mt-2 text-xs text-muted">
                    No skills exist yet — <Link to="/skills" className="text-accent-text hover:underline">add some on the Skills page</Link>.
                </p>
            ) : (
                available.length > 0 && (
                    <div className="mt-2 flex gap-2">
                        <label className="sr-only" htmlFor="add-skill">Add a skill</label>
                        <SelectInput id="add-skill" className="flex-1 py-1.5 text-sm" value={adding} onChange={(e) => setAdding(e.target.value)}>
                            <option value="">Add a skill…</option>
                            {available.map((s) => (
                                <option key={s.id} value={s.id}>{s.name}</option>
                            ))}
                        </SelectInput>
                        <button type="button" onClick={add} disabled={!adding} className="rounded-md border border-line bg-surface px-3 py-1.5 text-sm hover:bg-bg disabled:opacity-50">
                            Add
                        </button>
                    </div>
                )
            )}

            {error && <p className="mt-1 text-sm text-danger">{error}</p>}
        </div>
    );
}
