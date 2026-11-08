// "Export CSV" / "Export PDF" links for a report route that takes a {format}.
// `params` are the page's applied filters, so the file matches what is on screen.
// Plain <a> links (not Inertia) because the server answers with a file download.
export default function ExportButtons({ routeName, params = {} }) {
    // Drop empty filters so the URL stays clean.
    const clean = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
    );

    const cls =
        'inline-flex items-center gap-1 rounded-md border border-line bg-white px-3 py-2 text-sm font-medium text-content hover:bg-bg';

    return (
        <div className="flex items-center gap-2">
            <span className="text-xs text-muted">Export:</span>
            <a href={route(routeName, { ...clean, format: 'csv' })} className={cls} title="Spreadsheet (opens in Excel)">
                CSV
            </a>
            <a href={route(routeName, { ...clean, format: 'pdf' })} className={cls} title="Printable report">
                PDF
            </a>
        </div>
    );
}
