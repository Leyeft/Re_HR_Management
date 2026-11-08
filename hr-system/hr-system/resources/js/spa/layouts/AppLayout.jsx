import ApplicationLogo from '@/Components/ApplicationLogo';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { logout, selectAuth } from '../store/authSlice';
import { toastSuccess } from '../store/toastSlice';

// Top-menu links. `can` = the permission flag needed to see the link (none = everyone).
export const NAV_LINKS = [
    { to: '/', label: 'Dashboard', end: true },
    { to: '/departments', label: 'Departments', can: 'manage_staff' },
    { to: '/employees', label: 'Employees', can: 'manage_staff' },
    { to: '/leave', label: 'My Leave', can: 'request_leave' },
    { to: '/approvals', label: 'Approvals', can: 'approve_department_leave', end: true },
    { to: '/approvals/history', label: 'Dept. History', can: 'approve_department_leave' },
    { to: '/hr/leave', label: 'All Leave', can: 'sign_off_leave' },
    { to: '/settings/leave', label: 'Leave Settings', can: 'manage_staff' },
];

const desktopLink = ({ isActive }) =>
    'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium transition ' +
    (isActive ? 'border-accent text-white' : 'border-transparent text-white/70 hover:border-white/40 hover:text-white');

const mobileLink = ({ isActive }) =>
    'block border-l-4 py-2 pe-4 ps-3 text-base font-medium transition ' +
    (isActive ? 'border-accent bg-white/10 text-white' : 'border-transparent text-white/80 hover:bg-white/5 hover:text-white');

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

// Shell around every signed-in page: navigation bar + the page itself.
export default function AppLayout() {
    const { user, employee, can } = useSelector(selectAuth);
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const location = useLocation();
    const [mobileOpen, setMobileOpen] = useState(false);

    const links = NAV_LINKS.filter((link) => !link.can || can[link.can]);

    // Close the phone menu after navigating.
    useEffect(() => setMobileOpen(false), [location.pathname]);

    const signOut = async () => {
        await dispatch(logout());
        dispatch(toastSuccess('You have been signed out.'));
        navigate('/login', { replace: true });
    };

    const roleLine = [ROLE_LABELS[user.role], employee?.rank === 'head' ? 'Head of Department' : null]
        .filter(Boolean)
        .join(' · ');

    return (
        <div className="min-h-screen bg-bg">
            <nav className="border-b border-primary-hover bg-primary">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex min-w-0">
                            <Link to="/" className="flex shrink-0 items-center" aria-label="Crewly home">
                                <ApplicationLogo dark className="block h-9 w-auto" />
                            </Link>

                            <div className="hidden space-x-6 lg:-my-px lg:ms-8 lg:flex">
                                {links.map((link) => (
                                    <NavLink key={link.to} to={link.to} end={link.end} className={desktopLink}>
                                        {link.label}
                                    </NavLink>
                                ))}
                            </div>
                        </div>

                        {/* User menu (desktop) */}
                        <div className="hidden lg:ms-6 lg:flex lg:items-center">
                            <Menu as="div" className="relative">
                                <MenuButton className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white/80 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                    {user.name}
                                    <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fillRule="evenodd" d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z" clipRule="evenodd" />
                                    </svg>
                                </MenuButton>
                                <MenuItems
                                    anchor="bottom end"
                                    className="z-50 mt-2 w-56 rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-none"
                                >
                                    <div className="border-b border-line px-4 py-2">
                                        <p className="truncate text-sm font-medium text-content">{user.email}</p>
                                        <p className="text-xs text-muted">{roleLine}</p>
                                    </div>
                                    <MenuItem>
                                        <Link to="/profile" className="block px-4 py-2 text-sm text-content data-[focus]:bg-bg">
                                            Profile
                                        </Link>
                                    </MenuItem>
                                    <MenuItem>
                                        <button type="button" onClick={signOut} className="block w-full px-4 py-2 text-left text-sm text-content data-[focus]:bg-bg">
                                            Sign out
                                        </button>
                                    </MenuItem>
                                </MenuItems>
                            </Menu>
                        </div>

                        {/* Hamburger (tablet & phone) */}
                        <div className="-me-2 flex items-center lg:hidden">
                            <button
                                type="button"
                                onClick={() => setMobileOpen((open) => !open)}
                                className="inline-flex items-center justify-center rounded-md p-2 text-white/80 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                aria-expanded={mobileOpen}
                                aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    {mobileOpen ? (
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    ) : (
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                    )}
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Phone / tablet menu */}
                {mobileOpen && (
                    <div className="lg:hidden">
                        <div className="space-y-1 pb-3 pt-2">
                            {links.map((link) => (
                                <NavLink key={link.to} to={link.to} end={link.end} className={mobileLink}>
                                    {link.label}
                                </NavLink>
                            ))}
                        </div>
                        <div className="border-t border-white/10 pb-1 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-white">{user.name}</div>
                                <div className="text-sm text-white/60">{user.email} · {roleLine}</div>
                            </div>
                            <div className="mt-3 space-y-1">
                                <NavLink to="/profile" className={mobileLink}>Profile</NavLink>
                                <button
                                    type="button"
                                    onClick={signOut}
                                    className="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-left text-base font-medium text-white/80 hover:bg-white/5 hover:text-white"
                                >
                                    Sign out
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </nav>

            <main>
                <Outlet />
            </main>
        </div>
    );
}
