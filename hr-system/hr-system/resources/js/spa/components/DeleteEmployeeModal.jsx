import DangerButton from '@/Components/DangerButton';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import { useDispatch } from 'react-redux';
import { Link } from 'react-router-dom';
import { deleteEmployee } from '../store/employeesSlice';
import { toastError, toastSuccess } from '../store/toastSlice';

const CONFIRM_WORD = 'DELETE';

// Themed blockers for the accounts the system can't lose (codes come from the API).
const BLOCKERS = {
    last_admin: {
        icon: '👑',
        title: 'The crown cannot be left upon an empty throne!',
        body: 'Thou wouldst dethrone the last admin of this realm? With no ruler, the castle gates shall lock forever. Crown a worthy heir first, lest the realm fall into eternal darkness (and nobody gets paid on Friday).',
        action: { label: 'Crown an heir', to: '/employees/new' },
    },
    last_hr: {
        icon: '🛡️',
        title: 'A knight never strikes the last healer.',
        body: "'Tis the final HR of the kingdom! Slay them, and who shall tend the wounded spirits of the payroll-stricken? Appoint another healer first.",
        action: { label: 'Appoint a healer', to: '/employees/new' },
    },
    last_head: {
        icon: '⚔️',
        title: 'A castle without a lord falls by nightfall!',
        body: 'This is the only Head of their department. Strike them down and every leave request in that castle shall wander the halls forever, with no lord to grant it. Crown a new Head for the department first.',
        action: { label: 'Find a new Head', to: (e) => `/employees?department=${e.department_id}` },
    },
};

/**
 * Type-DELETE confirmation. If the server refuses because this is the last
 * Admin / HR / Head, the dialog switches to the matching blocker.
 */
export default function DeleteEmployeeModal({ employee, onClose, onDeleted }) {
    const dispatch = useDispatch();
    const [typed, setTyped] = useState('');
    const [busy, setBusy] = useState(false);
    const [blocked, setBlocked] = useState(null);

    // Known up front: the list tells us if this is a department's only Head.
    useEffect(() => {
        setTyped('');
        setBlocked(employee?.is_last_head ? 'last_head' : null);
    }, [employee]);

    const confirm = async () => {
        if (typed !== CONFIRM_WORD) return;
        setBusy(true);
        try {
            const res = await dispatch(deleteEmployee(employee.id)).unwrap();
            dispatch(toastSuccess(res.message));
            onDeleted?.();
            onClose();
        } catch (error) {
            if (BLOCKERS[error.code]) setBlocked(error.code);
            else dispatch(toastError(error.message));
        } finally {
            setBusy(false);
        }
    };

    const blocker = blocked && BLOCKERS[blocked];

    return (
        <Modal show={!!employee} onClose={onClose} maxWidth="md">
            {employee && blocker && (
                <div className="p-6">
                    <h2 className="text-lg font-medium text-content">{blocker.icon} {blocker.title}</h2>
                    <p className="mt-3 text-sm text-muted">{blocker.body}</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={onClose}>Lower my sword</SecondaryButton>
                        <Link
                            to={typeof blocker.action.to === 'function' ? blocker.action.to(employee) : blocker.action.to}
                            onClick={onClose}
                            className="inline-flex items-center rounded-md bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                        >
                            {blocker.action.label}
                        </Link>
                    </div>
                </div>
            )}

            {employee && !blocker && (
                <form
                    className="p-6"
                    onSubmit={(e) => {
                        e.preventDefault();
                        confirm();
                    }}
                >
                    <h2 className="text-lg font-medium text-content">Delete {employee.user?.name}?</h2>
                    <p className="mt-2 text-sm text-muted">
                        They won't be able to sign in, and any leave still waiting for approval is cancelled. Past leave stays on record, and you can restore them later from the Deleted list.
                    </p>
                    <div className="mt-4">
                        <InputLabel htmlFor="confirm-delete" value={`Type ${CONFIRM_WORD} to confirm`} />
                        <TextInput
                            id="confirm-delete"
                            className="mt-1 block w-full"
                            value={typed}
                            onChange={(e) => setTyped(e.target.value)}
                            autoComplete="off"
                            isFocused
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>Cancel</SecondaryButton>
                        <DangerButton disabled={typed !== CONFIRM_WORD || busy}>{busy ? 'Deleting…' : 'Delete employee'}</DangerButton>
                    </div>
                </form>
            )}
        </Modal>
    );
}
