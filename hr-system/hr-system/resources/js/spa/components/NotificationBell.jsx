import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate } from 'react-router-dom';
import {
    fetchNotifications,
    fetchUnreadCount,
    markAllNotificationsRead,
    markNotificationRead,
} from '../store/notificationsSlice';
import { timeAgo } from '../utils/time';
import Spinner from './Spinner';

const POLL_MS = 30_000;

/**
 * Bell in the top bar with the number of unread notifications. Checks for new ones
 * every 30 seconds while the tab is visible; opening it shows the latest few.
 */
export default function NotificationBell() {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { items, unreadCount, status } = useSelector((state) => state.notifications);

    useEffect(() => {
        dispatch(fetchUnreadCount());
        const timer = setInterval(() => {
            if (document.visibilityState === 'visible') dispatch(fetchUnreadCount());
        }, POLL_MS);
        return () => clearInterval(timer);
    }, [dispatch]);

    const open = (note, close) => {
        if (!note.read_at) dispatch(markNotificationRead(note.id));
        close();
        if (note.url) navigate(note.url);
    };

    return (
        <Popover className="relative">
            <PopoverButton
                onClick={() => dispatch(fetchNotifications({ per_page: 6 }))}
                className="relative rounded-full p-2 text-white/80 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                aria-label={unreadCount ? `Notifications, ${unreadCount} unread` : 'Notifications'}
            >
                <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.7 21a2 2 0 0 1-3.4 0" />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-danger px-1 text-[11px] font-bold text-white ring-2 ring-primary">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </PopoverButton>

            <PopoverPanel
                anchor="bottom end"
                className="z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-lg bg-white shadow-xl ring-1 ring-black/5 focus:outline-none"
            >
                {({ close }) => (
                    <>
                        <div className="flex items-center justify-between border-b border-line px-4 py-3">
                            <p className="text-sm font-semibold text-content">Notifications</p>
                            {unreadCount > 0 && (
                                <button type="button" onClick={() => dispatch(markAllNotificationsRead())} className="text-xs font-medium text-accent-text hover:underline">
                                    Mark all as read
                                </button>
                            )}
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {status === 'loading' && !items.length ? (
                                <div className="flex justify-center p-6"><Spinner className="h-6 w-6 text-primary" /></div>
                            ) : items.length === 0 ? (
                                <p className="p-6 text-center text-sm text-muted">You're all caught up.</p>
                            ) : (
                                <ul className="divide-y divide-line">
                                    {items.map((note) => (
                                        <li key={note.id}>
                                            <button
                                                type="button"
                                                onClick={() => open(note, close)}
                                                className="flex w-full gap-3 px-4 py-3 text-left hover:bg-bg"
                                            >
                                                <span
                                                    className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${note.read_at ? 'bg-transparent' : 'bg-accent'}`}
                                                    aria-label={note.read_at ? undefined : 'Unread'}
                                                />
                                                <span className="min-w-0">
                                                    <span className={`block text-sm ${note.read_at ? 'text-content' : 'font-semibold text-content'}`}>{note.title}</span>
                                                    <span className="block text-sm text-muted">{note.message}</span>
                                                    <span className="mt-1 block text-xs text-muted">{timeAgo(note.created_at)}</span>
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <Link to="/notifications" onClick={close} className="block border-t border-line px-4 py-2.5 text-center text-sm font-medium text-primary hover:bg-bg">
                            View all
                        </Link>
                    </>
                )}
            </PopoverPanel>
        </Popover>
    );
}
