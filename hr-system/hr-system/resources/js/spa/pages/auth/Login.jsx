import { brandPanel, loginCss } from '@/Shared/loginDesign';
import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import useDocumentTitle from '../../hooks/useDocumentTitle';
import { clearNotice, login, selectAuth } from '../../store/authSlice';
import { toastSuccess } from '../../store/toastSlice';

export default function Login() {
    useDocumentTitle('Sign in');
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const location = useLocation();
    const { notice } = useSelector(selectAuth);

    // Form fields are local to this page; who is signed in lives in the Redux store.
    const [form, setForm] = useState({ email: '', password: '', remember: false });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [showPw, setShowPw] = useState(false);

    // Why we're here: redirected from a private page, or the session ended.
    const message = location.state?.message ?? notice;

    useEffect(() => () => dispatch(clearNotice()), [dispatch]);

    const set = (field) => (e) =>
        setForm((f) => ({ ...f, [field]: e.target.type === 'checkbox' ? e.target.checked : e.target.value }));

    const submit = async (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        try {
            const profile = await dispatch(login(form)).unwrap();
            dispatch(toastSuccess(`Welcome back, ${profile.user.name}!`));
            navigate(location.state?.from?.pathname ?? '/', { replace: true });
        } catch (error) {
            // Field errors (wrong password, too many attempts) show under the inputs.
            setErrors(Object.keys(error.errors ?? {}).length ? mapErrors(error.errors) : { email: error.message });
            setForm((f) => ({ ...f, password: '' }));
        } finally {
            setProcessing(false);
        }
    };

    return (
        <>
            <style>{loginCss}</style>

            <div className="page">
                <section className="brand" aria-label="About Crewly" dangerouslySetInnerHTML={{ __html: brandPanel }} />

                <main className="form-side">
                    <div className="form-box">
                        <form onSubmit={submit} noValidate>
                            <div className="fade-up" style={{ animationDelay: '.1s' }}>
                                <h1>Welcome back</h1>
                                <p className="sub">Sign in to your Crewly workspace.</p>
                                {message && <p className="status">{message}</p>}
                            </div>

                            <div className="field fade-up" style={{ animationDelay: '.2s' }}>
                                <label htmlFor="email">Work email</label>
                                <div className="input-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-muted" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" /></svg>
                                    <input
                                        id="email"
                                        type="email"
                                        autoComplete="username"
                                        autoFocus
                                        placeholder="name@company.com"
                                        className={'input' + (errors.email ? ' invalid' : '')}
                                        value={form.email}
                                        onChange={set('email')}
                                        aria-invalid={!!errors.email}
                                        aria-describedby="email-error"
                                    />
                                </div>
                                <span id="email-error" className="error" role="alert">{errors.email}</span>
                            </div>

                            <div className="field fade-up" style={{ animationDelay: '.3s' }}>
                                <div className="field-row">
                                    <label htmlFor="password">Password</label>
                                    <Link to="/forgot-password">Forgot password?</Link>
                                </div>
                                <div className="input-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-muted" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>
                                    <input
                                        id="password"
                                        type={showPw ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        placeholder="Enter your password"
                                        className={'input has-toggle' + (errors.password ? ' invalid' : '')}
                                        value={form.password}
                                        onChange={set('password')}
                                        aria-invalid={!!errors.password}
                                        aria-describedby="password-error"
                                    />
                                    <button
                                        type="button"
                                        className="eye"
                                        aria-label={showPw ? 'Hide password' : 'Show password'}
                                        onClick={() => setShowPw((v) => !v)}
                                    >
                                        {showPw ? (
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-muted" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M3 3l18 18" /><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                        ) : (
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-muted" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                        )}
                                    </button>
                                </div>
                                <span id="password-error" className="error" role="alert">{errors.password}</span>
                            </div>

                            <div className="fade-up" style={{ animationDelay: '.4s' }}>
                                <label className="remember">
                                    <input type="checkbox" checked={form.remember} onChange={set('remember')} /> Keep me signed in
                                </label>
                            </div>

                            <div className="fade-up" style={{ animationDelay: '.5s', display: 'flex', flexDirection: 'column', gap: '16px' }}>
                                <button type="submit" className="btn btn-primary" disabled={processing}>
                                    <svg className={'spinner' + (processing ? '' : ' hidden')} width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="rgba(255,255,255,.3)" strokeWidth="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="#2EC4B6" strokeWidth="3" strokeLinecap="round" /></svg>
                                    <span>{processing ? 'Signing in…' : 'Sign in'}</span>
                                </button>
                                <div className="divider">or</div>
                                <button type="button" className="btn btn-sso" aria-disabled="true" title="Company SSO is under development">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-muted" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6l8-3z" /><path d="M9 12l2 2 4-4" /></svg>
                                    Sign in with company SSO
                                    <span className="tag">Under development</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </>
    );
}

// API validation errors come as { field: ["message", …] } — show the first message per field.
function mapErrors(errors) {
    return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0]]));
}
