// "just now", "5 min ago", "3 h ago", "2 days ago", or a date for anything older than a week.
export function timeAgo(iso) {
    if (!iso) return '';
    const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes} min ago`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours} h ago`;
    const days = Math.round(hours / 24);
    if (days < 7) return `${days} day${days === 1 ? '' : 's'} ago`;
    return formatDateTime(iso, false);
}

// "8 Nov 2026, 15:04" (local time) — or just the date.
export function formatDateTime(iso, withTime = true) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}
