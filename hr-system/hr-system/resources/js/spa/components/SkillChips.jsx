const LEVEL_STYLE = {
    beginner: 'bg-bg text-muted',
    intermediate: 'bg-accent-tint text-accent-text',
    expert: 'bg-primary text-white',
};

// An employee's skills as small labels, e.g. "Excel · Expert". The level is written
// out (not shown by colour alone).
export default function SkillChips({ skills, empty = 'No skills recorded.' }) {
    if (!skills?.length) return <p className="text-sm text-muted">{empty}</p>;

    return (
        <ul className="flex flex-wrap gap-2">
            {skills.map((s) => (
                <li key={s.id} className={`rounded-full px-3 py-1 text-xs font-medium ${LEVEL_STYLE[s.level] ?? LEVEL_STYLE.beginner}`}>
                    {s.name} · {s.level.charAt(0).toUpperCase() + s.level.slice(1)}
                </li>
            ))}
        </ul>
    );
}
