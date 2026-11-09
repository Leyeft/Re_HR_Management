import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import Page from '../components/Page';
import Pagination from '../components/Pagination';
import { PageLoader } from '../components/Spinner';
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from '../store/notificationsSlice';
import { formatDateTime, timeAgo } from '../utils/time';

// /notifications — every notification, newest first, with an "unread only" filter.
export default function Notifications() {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { items, meta, unreadCount, status } = useSelector((state) => state.notifications);
    const [unreadOnly, setUnreadOnly] = useState(false);

    const load = (page = 1) => dispatch(fetchNotifications({ page, per_page: 15, ...(unreadOnly ? { unread: 1 } : {}) }));
    useEffect(() => {
        load();
    }, [unreadOnly]); // eslint-disable-line react-hooks/exhaustive-deps

    const open = (note) => {
        if (!note.read_at) dispatch(markNotificationRead(note.id));
        if (note.url) navigate(note.url);
    };

    return (
        <Page
            title="Notifications"
            width="max-w-3xl"
            actions={
                unreadCount > 0 && (
                    <button type="button" onClick={() => dispatch(markAllNotificationsRead())} className="rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium hover:bg-bg">
                        Mark all as read
                    </button>
                )
            }
        >
            <label className="flex w-fit cursor-pointer items-center gap-2 text-sm text-content">
                <input type="checkbox" className="rounded border-line text-accent focus:ring-accent" checked={unreadOnly} onChange={(e) => setUnreadOnly(e.target.checked)} />
                Unread only ({unreadCount})
            </label>

            <section className="rounded-lg bg-surface shadow">
                {status === 'loading' && !items.length ? (
                    <PageLoader label="Loading notifications…" />
                ) : items.length === 0 ? (
                    <p className="p-8 text-center text-sm text-muted">{unreadOnly ? 'No unread notifications.' : 'No notifications yet.'}</p>
                ) : (
                    <ul className="divide-y divide-line">
                        {items.map((note) => (
                            <li key={note.id}>
                                <button type="button" onClick={() => open(note)} className="flex w-full gap-4 p-4 text-left hover:bg-bg">
                                    <span className={`mt-2 h-2.5 w-2.5 shrink-0 rounded-full ${note.read_at ? 'bg-line' : 'bg-accent'}`} aria-label={note.read_at ? 'Read' : 'Unread'} />
                                    <span className="min-w-0 flex-1">
                                        <span className="flex flex-wrap items-baseline justify-between gap-2">
                                            <span className={`text-sm ${note.read_at ? 'text-content' : 'font-semibold text-content'}`}>{note.title}</span>
                                            <time dateTime={note.created_at} title={formatDateTime(note.created_at)} className="text-xs text-muted">
                                                {timeAgo(note.created_at)}
                                            </time>
                                        </span>
                                        <span className="mt-0.5 block text-sm text-muted">{note.message}</span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="px-4 pb-4">
                    <Pagination meta={meta} onPage={load} label="notifications" />
                </div>
            </section>
        </Page>
    );
}
