// Shows this year's leave allowances by type (annual / sick / professional).
// `summary` comes from Employee::leaveSummary() on the server.
function Stat({ title, main, sub, tone = 'text-primary' }) {
    return (
        <div className="rounded-lg border border-line p-4">
            <p className="text-xs uppercase tracking-wide text-muted">{title}</p>
            <p className={`mt-1 text-2xl font-bold ${tone}`}>{main}</p>
            {sub && <p className="mt-1 text-xs text-muted">{sub}</p>}
        </div>
    );
}

export default function LeaveBalance({ summary }) {
    if (!summary) return null;

    const { tier, year, annual, sick, professional } = summary;

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-medium text-muted">Leave balance · {year}</h3>
                <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">
                    {tier} · {annual.entitlement} annual days/yr
                </span>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Stat
                    title="Annual leave"
                    main={`${annual.remaining} / ${annual.entitlement}`}
                    sub={`left · ${annual.taken} used · ${annual.pending} pending`}
                    tone="text-primary"
                />
                <Stat
                    title="Sick leave"
                    main={`${sick.remaining} / ${sick.cap}`}
                    sub={`left · ${sick.taken} used`}
                    tone="text-info"
                />
                <Stat
                    title="Professional leave"
                    main={professional.taken}
                    sub={`days used · no limit${professional.pending ? ` · ${professional.pending} pending` : ''}`}
                    tone="text-accent-text"
                />
            </div>
        </div>
    );
}
