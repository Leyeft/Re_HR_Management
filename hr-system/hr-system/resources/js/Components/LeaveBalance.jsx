// Shows this year's leave entitlement, how much is used/pending, and what's left.
// `summary` comes from Employee::leaveSummary() on the server.
export default function LeaveBalance({ summary, compact = false }) {
    if (!summary) return null;

    const { tier, entitlement, taken, pending, remaining, year } = summary;

    if (compact) {
        return (
            <span className="inline-flex items-center gap-2 text-sm">
                <span className="rounded bg-accent-tint px-2 py-0.5 text-xs font-medium text-accent-text">
                    {tier}
                </span>
                <span className="text-muted">
                    {remaining}/{entitlement} days left
                </span>
            </span>
        );
    }

    const stats = [
        { label: 'Entitlement', value: entitlement, tone: 'text-primary' },
        { label: 'Used', value: taken, tone: 'text-success' },
        { label: 'Pending', value: pending, tone: 'text-info' },
        { label: 'Remaining', value: remaining, tone: 'text-content' },
    ];

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-medium text-muted">Leave balance · {year}</h3>
                <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">
                    {tier} · {entitlement} days/yr
                </span>
            </div>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                {stats.map((s) => (
                    <div key={s.label}>
                        <p className="text-xs uppercase tracking-wide text-muted">{s.label}</p>
                        <p className={`text-2xl font-bold ${s.tone}`}>{s.value}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}
