import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { dismissToast } from '../store/toastSlice';

const STYLES = {
    success: { box: 'border-success bg-success-bg text-success', icon: 'M5 12l5 5L20 7' },
    error: { box: 'border-danger bg-danger-bg text-danger', icon: 'M12 8v5m0 3.5v.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z' },
    info: { box: 'border-info bg-info-bg text-info', icon: 'M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0z' },
};

function Toast({ toast }) {
    const dispatch = useDispatch();
    const style = STYLES[toast.type] ?? STYLES.info;

    // Errors stay a little longer so there's time to read them.
    useEffect(() => {
        const timer = setTimeout(() => dispatch(dismissToast(toast.id)), toast.type === 'error' ? 7000 : 4000);
        return () => clearTimeout(timer);
    }, [dispatch, toast.id, toast.type]);

    return (
        <div
            role={toast.type === 'error' ? 'alert' : 'status'}
            className={`pointer-events-auto flex w-full items-start gap-3 rounded-lg border-l-4 p-4 shadow-lg ${style.box}`}
        >
            <svg className="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d={style.icon} />
            </svg>
            <p className="flex-1 text-sm font-medium">{toast.message}</p>
            <button
                type="button"
                onClick={() => dispatch(dismissToast(toast.id))}
                className="text-current opacity-60 hover:opacity-100"
                aria-label="Dismiss"
            >
                ✕
            </button>
        </div>
    );
}

// Stack of toasts in the top-right corner (top-centre on phones).
export default function Toasts() {
    const toasts = useSelector((state) => state.toasts);

    return (
        <div
            aria-live="polite"
            className="pointer-events-none fixed inset-x-4 top-4 z-[60] flex flex-col items-end gap-2 sm:left-auto sm:right-4 sm:w-96"
        >
            {toasts.map((toast) => (
                <Toast key={toast.id} toast={toast} />
            ))}
        </div>
    );
}
