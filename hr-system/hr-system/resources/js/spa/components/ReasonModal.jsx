import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { useEffect } from 'react';
import useApiForm from '../hooks/useApiForm';

/**
 * Ask for a reason before a step that needs one (reject, cancel).
 * `onConfirm(reason)` returns a promise; API field errors appear under the box.
 */
export default function ReasonModal({ show, title, description, label, confirmLabel, cancelLabel = 'Back', errorField, onConfirm, onClose }) {
    const form = useApiForm({ reason: '' });

    useEffect(() => {
        if (show) form.reset({ reason: '' });
    }, [show]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (e) => {
        e.preventDefault();
        form.submit((data) => onConfirm(data.reason.trim()), { onSuccess: onClose });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6" noValidate>
                <h2 className="text-lg font-medium text-content">{title}</h2>
                {description && <p className="mt-2 text-sm text-muted">{description}</p>}
                <div className="mt-4">
                    <InputLabel htmlFor="reason" value={label} />
                    <textarea
                        id="reason"
                        rows="3"
                        maxLength={255}
                        className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                        value={form.data.reason}
                        onChange={form.field('reason')}
                        autoFocus
                    />
                    <div className="mt-1 flex justify-between gap-2">
                        <InputError message={form.errors[errorField]} />
                        <span className="ms-auto text-xs text-muted">{form.data.reason.length}/255</span>
                    </div>
                </div>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>{cancelLabel}</SecondaryButton>
                    <DangerButton disabled={form.processing || !form.data.reason.trim()}>
                        {form.processing ? 'Saving…' : confirmLabel}
                    </DangerButton>
                </div>
            </form>
        </Modal>
    );
}
