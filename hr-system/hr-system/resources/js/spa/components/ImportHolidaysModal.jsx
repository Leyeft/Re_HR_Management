import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useEffect, useRef, useState } from 'react';
import { useDispatch } from 'react-redux';
import api from '../api/client';
import { fetchHolidays } from '../store/holidaysSlice';
import { toastSuccess } from '../store/toastSlice';
import Spinner from './Spinner';

const STATUS = {
    new: { label: 'Will be added', cls: 'bg-success-bg text-success' },
    replace: { label: 'Will replace', cls: 'bg-info-bg text-info' },
    unchanged: { label: 'No change', cls: 'bg-bg text-muted' },
    duplicate: { label: 'Duplicate', cls: 'bg-warning-bg text-warning' },
    error: { label: 'Error', cls: 'bg-danger-bg text-danger' },
};

/**
 * Bulk-add public holidays from Excel / CSV (upload each year's list):
 *   1. download the template   2. choose a file → preview every row   3. import.
 * New dates are added; a date that's already saved gets the file's name; other saved
 * dates are kept. Nothing is saved until "Import" is clicked.
 */
export default function ImportHolidaysModal({ show, onClose }) {
    const dispatch = useDispatch();
    const inputRef = useRef(null);
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null); // { rows, summary }
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (show) {
            setFile(null);
            setPreview(null);
            setError(null);
        }
    }, [show]);

    const send = async (chosen, dryRun) => {
        const body = new FormData();
        body.append('file', chosen);
        body.append('dry_run', dryRun ? '1' : '0');
        return (await api.post('/holidays/import', body)).data;
    };

    // Step 2: check the chosen file (nothing saved yet).
    const choose = async (e) => {
        const chosen = e.target.files?.[0];
        e.target.value = '';
        if (!chosen) return;
        setFile(chosen);
        setPreview(null);
        setError(null);
        setBusy(true);
        try {
            setPreview((await send(chosen, true)).data);
        } catch (err) {
            setError(err.errors?.file?.[0] ?? err.message);
        } finally {
            setBusy(false);
        }
    };

    // Step 3: save the rows marked "Will be added".
    const runImport = async () => {
        setBusy(true);
        try {
            const res = await send(file, false);
            dispatch(toastSuccess(res.message));
            dispatch(fetchHolidays());
            onClose();
        } catch (err) {
            setError(err.message);
        } finally {
            setBusy(false);
        }
    };

    const s = preview?.summary;
    const changes = s ? s.new + s.replace : 0;

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="space-y-5 p-6">
                <div>
                    <h2 className="text-lg font-medium text-content">Import holidays from Excel or CSV</h2>
                    <p className="mt-1 text-sm text-muted">
                        Upload this year's holidays. New dates are added, a date that's already saved gets the name from your file,
                        and other saved dates stay. You'll see a preview before anything is saved.
                    </p>
                </div>

                {/* Step 1 */}
                <section className="rounded-md border border-line p-4">
                    <p className="text-sm font-medium text-content">1. Download the template</p>
                    <p className="mt-1 text-xs text-muted">
                        Two columns: <b>date</b> (2026-12-25 or 25/12/2026) and <b>name</b>. Replace the example rows with your own.
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <a href="/templates/holidays.xlsx" className="rounded-md border border-line bg-white px-3 py-1.5 text-sm font-medium hover:bg-bg">Excel template (.xlsx)</a>
                        <a href="/templates/holidays.csv" className="rounded-md border border-line bg-white px-3 py-1.5 text-sm font-medium hover:bg-bg">CSV template (.csv)</a>
                    </div>
                </section>

                {/* Step 2 */}
                <section className="rounded-md border border-line p-4">
                    <p className="text-sm font-medium text-content">2. Choose your filled-in file</p>
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <input ref={inputRef} type="file" accept=".xlsx,.csv" className="sr-only" id="holiday-file" onChange={choose} />
                        <SecondaryButton type="button" onClick={() => inputRef.current?.click()} disabled={busy}>
                            {file ? 'Choose another file' : 'Choose file'}
                        </SecondaryButton>
                        {file && <span className="truncate text-sm text-muted">{file.name}</span>}
                        {busy && <Spinner className="h-5 w-5 text-primary" label="Checking file" />}
                    </div>
                    {error && <p role="alert" className="mt-3 rounded-md bg-danger-bg p-3 text-sm text-danger">{error}</p>}
                </section>

                {/* Preview */}
                {preview && (
                    <section>
                        <p className="text-sm text-content" aria-live="polite">
                            <b>{s.new}</b> to add
                            {s.replace > 0 && <> · <b>{s.replace}</b> to replace</>}
                            {s.unchanged > 0 && <> · {s.unchanged} unchanged</>}
                            {s.duplicate > 0 && <> · {s.duplicate} duplicate</>}
                            {s.error > 0 && <> · <span className="text-danger">{s.error} with errors (skipped)</span></>}
                        </p>
                        <div className="mt-2 max-h-72 overflow-y-auto rounded-md border border-line">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 bg-bg text-left text-xs uppercase text-muted">
                                    <tr>
                                        <th className="p-2">Row</th>
                                        <th className="p-2">Date</th>
                                        <th className="p-2">Name</th>
                                        <th className="p-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {preview.rows.map((r) => (
                                        <tr key={r.row} className="border-t border-line align-top">
                                            <td className="p-2 tabular-nums text-muted">{r.row}</td>
                                            <td className="whitespace-nowrap p-2">{r.date || '—'}</td>
                                            <td className="p-2">{r.name || '—'}</td>
                                            <td className="p-2">
                                                <span className={`rounded px-2 py-0.5 text-xs font-medium ${STATUS[r.status].cls}`}>{STATUS[r.status].label}</span>
                                                {r.message && <p className="mt-1 text-xs text-muted">{r.message}</p>}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}

                {/* Step 3 */}
                <div className="flex justify-end gap-3 border-t border-line pt-4">
                    <SecondaryButton type="button" onClick={onClose}>Cancel</SecondaryButton>
                    <PrimaryButton type="button" onClick={runImport} disabled={busy || changes === 0}>
                        {changes ? `3. Import (${[s.new && `${s.new} new`, s.replace && `${s.replace} replaced`].filter(Boolean).join(', ')})` : '3. Import'}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
