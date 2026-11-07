const styles = {
    annual: 'bg-accent-tint text-accent-text',
    sick: 'bg-info-bg text-info',
    professional: 'bg-warning-bg text-warning',
};

const labels = { annual: 'Annual', sick: 'Sick', professional: 'Professional' };

// Small coloured chip for a leave request's type.
export default function LeaveTypeBadge({ type }) {
    return (
        <span className={`rounded px-2 py-1 text-xs font-medium ${styles[type] ?? 'bg-bg text-muted'}`}>
            {labels[type] ?? type}
        </span>
    );
}
