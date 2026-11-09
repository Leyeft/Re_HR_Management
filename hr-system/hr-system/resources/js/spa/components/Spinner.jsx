// Small spinning loader. `label` is read by screen readers.
export default function Spinner({ className = 'h-5 w-5', label = 'Loading' }) {
    return (
        <svg className={`animate-spin ${className}`} viewBox="0 0 24 24" fill="none" role="status" aria-label={label}>
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity=".2" strokeWidth="3" />
            <path d="M21 12a9 9 0 0 0-9-9" stroke="var(--color-accent)" strokeWidth="3" strokeLinecap="round" />
        </svg>
    );
}

// Centred loader for a whole page or section.
export function PageLoader({ label = 'Loading…' }) {
    return (
        <div className="flex min-h-[40vh] flex-col items-center justify-center gap-3 text-muted">
            <Spinner className="h-8 w-8 text-primary-text" label={label} />
            <p className="text-sm">{label}</p>
        </div>
    );
}
