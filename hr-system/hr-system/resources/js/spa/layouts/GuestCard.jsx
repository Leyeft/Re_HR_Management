import ApplicationLogo from '@/Components/ApplicationLogo';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import useDocumentTitle from '../hooks/useDocumentTitle';
import { selectTheme } from '../store/themeSlice';

// Small centred card for the password pages (signed-out users).
export default function GuestCard({ title, intro, children }) {
    useDocumentTitle(title);
    const dark = useSelector(selectTheme) === 'dark';

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-12">
            <Link to="/login" aria-label="Crewly sign in">
                <ApplicationLogo dark={dark} className="h-10 w-auto" />
            </Link>
            <main className="mt-8 w-full max-w-md rounded-lg bg-surface p-6 shadow sm:p-8">
                <h1 className="text-2xl font-semibold text-primary-text">{title}</h1>
                {intro && <p className="mt-2 text-sm text-muted">{intro}</p>}
                <div className="mt-6">{children}</div>
            </main>
            <Link to="/login" className="mt-6 text-sm text-accent-text hover:text-primary-text">
                ← Back to sign in
            </Link>
        </div>
    );
}
