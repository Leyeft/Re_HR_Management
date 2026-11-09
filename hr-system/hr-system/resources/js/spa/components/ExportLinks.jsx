// "Export: CSV | PDF" links. The server sends a file, so these are normal links
// (the session cookie signs them in). `path` is e.g. '/reports/leave' and
// `params` are the page's current filters, so the file matches the screen.
export default function ExportLinks({ path, params = {} }) {
    const query = new URLSearchParams(
        Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
    ).toString();
    const href = (format) => `${path}.${format}${query ? `?${query}` : ''}`;

    const cls = 'inline-flex items-center rounded-md border border-line bg-surface px-3 py-2 text-sm font-medium text-content hover:bg-bg';

    return (
        <div className="flex items-center gap-2">
            <span className="text-xs text-muted">Export:</span>
            <a href={href('csv')} className={cls} title="Spreadsheet (opens in Excel)">CSV</a>
            <a href={href('pdf')} className={cls} title="Printable report">PDF</a>
        </div>
    );
}
