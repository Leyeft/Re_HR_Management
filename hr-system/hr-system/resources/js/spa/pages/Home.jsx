import PageHeader from '@/Components/PageHeader';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import useDocumentTitle from '../hooks/useDocumentTitle';
import { NAV_LINKS } from '../layouts/AppLayout';
import { selectAuth } from '../store/authSlice';

// Temporary start page while the real dashboard is being moved over (phase 3).
export default function Home() {
    useDocumentTitle('Dashboard');
    const { user, employee, can } = useSelector(selectAuth);
    const links = NAV_LINKS.filter((l) => l.to !== '/' && (!l.can || can[l.can]));

    return (
        <>
            <header className="bg-white shadow">
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <PageHeader>Dashboard</PageHeader>
                </div>
            </header>

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="text-xl font-semibold text-content">Hello, {user.name}</h2>
                    <p className="mt-1 text-sm text-muted">
                        {employee
                            ? `${employee.department?.name} · Employee ID ${employee.employee_code}`
                            : 'Your account has no employee record.'}
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {links.map((link) => (
                        <Link
                            key={link.to}
                            to={link.to}
                            className="rounded-lg border border-line bg-white p-5 shadow-sm transition hover:border-accent hover:shadow"
                        >
                            <p className="font-medium text-primary">{link.label}</p>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
