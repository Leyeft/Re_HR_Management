import { Link } from 'react-router-dom';
import useDocumentTitle from '../hooks/useDocumentTitle';

// Placeholder for pages that haven't moved to the SPA yet (removed in phase 3).
export default function ComingSoon({ title }) {
    useDocumentTitle(title);

    return (
        <div className="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
            <h1 className="text-2xl font-semibold text-primary">{title}</h1>
            <p className="mt-2 text-muted">This page is being moved to the new app and will be here soon.</p>
            <Link to="/" className="mt-6 inline-block rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">
                Back to dashboard
            </Link>
        </div>
    );
}
