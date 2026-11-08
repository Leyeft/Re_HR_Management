import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from 'react-router-dom';
import useDocumentTitle from '../hooks/useDocumentTitle';

// Small centred card for the password pages (signed-out users).
export default function GuestCard({ title, intro, children }) {
    useDocumentTitle(title);

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-12">
            <Link to="/login" aria-label="Crewly sign in">
                <ApplicationLogo className="h-10 w-auto" />
            </Link>
            <main className="mt-8 w-full max-w-md rounded-lg bg-white p-6 shadow sm:p-8">
                <h1 className="text-2xl font-semibold text-primary">{title}</h1>
                {intro && <p className="mt-2 text-sm text-muted">{intro}</p>}
                <div className="mt-6">{children}</div>
            </main>
            <Link to="/login" className="mt-6 text-sm text-accent-text hover:text-primary">
                ← Back to sign in
            </Link>
        </div>
    );
}
