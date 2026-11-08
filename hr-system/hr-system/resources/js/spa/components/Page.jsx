import PageHeader from '@/Components/PageHeader';
import useDocumentTitle from '../hooks/useDocumentTitle';

/**
 * Standard page frame: white title bar (with optional actions on the right),
 * then the content in a centred column. Also sets the browser tab title.
 */
export default function Page({ title, actions, width = 'max-w-7xl', children }) {
    useDocumentTitle(title);

    return (
        <>
            <header className="bg-white shadow">
                <div className={`mx-auto flex flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6 lg:px-8 ${width}`}>
                    <PageHeader>{title}</PageHeader>
                    {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
                </div>
            </header>
            <div className={`mx-auto space-y-6 px-4 py-8 sm:px-6 lg:px-8 ${width}`}>{children}</div>
        </>
    );
}

// White card with an optional heading.
export function Card({ title, description, children, className = '' }) {
    return (
        <section className={`rounded-lg bg-white p-6 shadow ${className}`}>
            {title && <h3 className="text-lg font-medium text-content">{title}</h3>}
            {description && <p className="mt-1 text-sm text-muted">{description}</p>}
            <div className={title || description ? 'mt-4' : ''}>{children}</div>
        </section>
    );
}

// Message shown when loading a page's data failed, with a retry button.
export function LoadError({ message, onRetry }) {
    return (
        <div role="alert" className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-danger bg-danger-bg p-4 text-sm text-danger">
            <span>{message ?? 'This page could not be loaded.'}</span>
            {onRetry && (
                <button type="button" onClick={onRetry} className="rounded-md bg-white px-3 py-1.5 font-medium text-danger shadow-sm hover:bg-bg">
                    Try again
                </button>
            )}
        </div>
    );
}
