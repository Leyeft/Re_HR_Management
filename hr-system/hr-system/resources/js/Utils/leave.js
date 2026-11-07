export const LEAVE_TYPE_LABELS = {
    annual: 'Annual',
    sick: 'Sick',
    professional: 'Professional',
};

// Leave entitlement tiers by years of service. Mirrors App\Models\Employee.
export function tierFromYears(years) {
    if (years < 1) return { label: 'Under 1 year', days: 3 };
    if (years <= 7) return { label: '1–7 years', days: 10 };
    if (years <= 15) return { label: '8–15 years', days: 15 };
    return { label: '15+ years', days: 20 };
}

// Whole years between a YYYY-MM-DD date and today.
export function yearsSince(dateStr) {
    if (!dateStr) return 0;
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return 0;
    const now = new Date();
    let years = now.getFullYear() - d.getFullYear();
    const m = now.getMonth() - d.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < d.getDate())) years--;
    return Math.max(0, years);
}

// Inclusive day count between two YYYY-MM-DD dates (null if invalid).
export function inclusiveDays(start, end) {
    if (!start || !end) return null;
    const a = new Date(start);
    const b = new Date(end);
    if (isNaN(a.getTime()) || isNaN(b.getTime()) || b < a) return null;
    return Math.round((b - a) / 86400000) + 1;
}
