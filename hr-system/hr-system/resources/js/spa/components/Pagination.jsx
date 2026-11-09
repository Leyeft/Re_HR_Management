// Page links for a Laravel paginated API response (`meta` = current_page, last_page, total, from, to).
export default function Pagination({ meta, onPage, label = 'results' }) {
    if (!meta || meta.total === 0) return null;

    const { current_page: page, last_page: last, from, to, total } = meta;

    // Show first, last, and two pages either side of the current one.
    const pages = [];
    for (let p = 1; p <= last; p++) {
        if (p === 1 || p === last || Math.abs(p - page) <= 2) pages.push(p);
        else if (pages[pages.length - 1] !== '…') pages.push('…');
    }

    const btn = 'min-w-9 rounded-md px-3 py-1.5 text-sm';

    return (
        <nav className="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
            <p className="text-sm text-muted">
                Showing {from}–{to} of {total} {label}
            </p>
            {last > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    <button type="button" className={`${btn} border border-line bg-surface disabled:opacity-40`} disabled={page === 1} onClick={() => onPage(page - 1)}>
                        ‹ Prev
                    </button>
                    {pages.map((p, i) =>
                        p === '…' ? (
                            <span key={`gap-${i}`} className="px-1 text-muted">…</span>
                        ) : (
                            <button
                                key={p}
                                type="button"
                                onClick={() => onPage(p)}
                                aria-current={p === page ? 'page' : undefined}
                                className={`${btn} ${p === page ? 'bg-primary text-white' : 'border border-line bg-surface hover:bg-bg'}`}
                            >
                                {p}
                            </button>
                        ),
                    )}
                    <button type="button" className={`${btn} border border-line bg-surface disabled:opacity-40`} disabled={page === last} onClick={() => onPage(page + 1)}>
                        Next ›
                    </button>
                </div>
            )}
        </nav>
    );
}
