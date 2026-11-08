import { Link } from 'react-router-dom';
import useDocumentTitle from '../hooks/useDocumentTitle';

export default function NotFound() {
    useDocumentTitle('Page not found');

    return (
        <div className="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
            <p className="text-6xl font-bold text-accent">404</p>
            <h1 className="mt-4 text-2xl font-semibold text-primary">We couldn't find that page</h1>
            <p className="mt-2 text-muted">The link may be old, or the page may have moved.</p>
            <Link to="/" className="mt-6 rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">
                Go to the dashboard
            </Link>
        </div>
    );
}
