import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { PageLoader } from '../components/Spinner';
import { selectAuth } from '../store/authSlice';
import { toastError } from '../store/toastSlice';

/**
 * Private routes. These only decide what to SHOW — every API endpoint checks
 * the same permissions again on the server.
 */

// Signed-in users only. Others go to /login with a message, and come back here after.
export function RequireAuth() {
    const { status } = useSelector(selectAuth);
    const location = useLocation();

    if (status === 'idle' || status === 'checking') {
        return <PageLoader label="Checking your session…" />;
    }

    if (status !== 'authenticated') {
        return (
            <Navigate
                to="/login"
                replace
                state={{ from: location, message: 'Please sign in to see that page.' }}
            />
        );
    }

    return <Outlet />;
}

// Signed-in users who have a permission flag (from GET /api/auth/me → can).
// Without it: back to the dashboard with an explanation.
export function RequireCan({ permission }) {
    const { can } = useSelector(selectAuth);

    if (!can?.[permission]) {
        return <Denied />;
    }

    return <Outlet />;
}

function Denied() {
    const dispatch = useDispatch();

    useEffect(() => {
        dispatch(toastError("You don't have access to that page."));
    }, [dispatch]);

    return <Navigate to="/" replace />;
}

// Login & password pages: if already signed in, go to where you were heading.
export function GuestOnly() {
    const { status } = useSelector(selectAuth);
    const location = useLocation();

    if (status === 'idle' || status === 'checking') {
        return <PageLoader label="Loading…" />;
    }

    if (status === 'authenticated') {
        return <Navigate to={location.state?.from?.pathname ?? '/'} replace />;
    }

    return <Outlet />;
}
