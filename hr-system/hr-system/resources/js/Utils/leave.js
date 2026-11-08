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
    return { label: '16+ years', days: 20 };
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

// Working days between two YYYY-MM-DD dates, inclusive (null if invalid): Mon–Fri,
// skipping public holidays. `holidays` = { dates: { '2026-04-13': true, … } }
// (from selectHolidayLookup in the holidays slice).
// Mirrors App\Models\LeaveRequest::workingDaysBetween.
export function workingDays(start, end, holidays = null) {
    if (!start || !end) return null;
    // Parse as UTC so time zones / DST can't shift the weekday.
    const a = new Date(start + 'T00:00:00Z');
    const b = new Date(end + 'T00:00:00Z');
    if (isNaN(a.getTime()) || isNaN(b.getTime()) || b < a) return null;
    let count = 0;
    for (const d = new Date(a); d <= b; d.setUTCDate(d.getUTCDate() + 1)) {
        const day = d.getUTCDay();
        const iso = d.toISOString().slice(0, 10);
        const isHoliday = !!holidays?.dates?.[iso];
        if (day !== 0 && day !== 6 && !isHoliday) count++;
    }
    return count;
}
