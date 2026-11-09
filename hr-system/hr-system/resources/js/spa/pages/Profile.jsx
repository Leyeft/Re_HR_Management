// Profile page (everyone): email verification banner, profile photo, name/email, and a link to change the password.
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useRef, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../api/client';
import Avatar from '../components/Avatar';
import { formatDateTime } from '../utils/time';
import { canSeeSalary, formatSalary } from '../utils/money';
import Page, { Card } from '../components/Page';
import useApiForm from '../hooks/useApiForm';
import { fetchMe, selectAuth, userUpdated } from '../store/authSlice';
import { toastError, toastSuccess } from '../store/toastSlice';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

export default function Profile() {
    const { user, employee } = useSelector(selectAuth);

    return (
        <Page title="Profile" width="max-w-3xl">
            <Card title="Your account">
                <dl className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <Item label="Role">{ROLE_LABELS[user.role]}</Item>
                    <Item label="Employee ID">{employee?.employee_code ?? '—'}</Item>
                    <Item label="Department">{employee?.department?.name ?? '—'}</Item>
                    <Item label="Rank">{employee?.rank_label ?? '—'}</Item>
                    {canSeeSalary(employee) && <Item label="Monthly salary">{formatSalary(employee.salary)}</Item>}
                </dl>
                <p className="mt-4 text-xs text-muted">
                    Role, department, rank and salary are managed by HR. Only you, HR and Admin can see your salary.
                    {user.last_login_at && ` Last sign-in: ${formatDateTime(user.last_login_at)}.`}
                </p>
            </Card>

            <VerifyEmailBanner user={user} />
            <AvatarCard user={user} />
            <ProfileForm user={user} />
            <PasswordLink />
        </Page>
    );
}

function Item({ label, children }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-muted">{label}</dt>
            <dd className="mt-1 font-medium text-content">{children}</dd>
        </div>
    );
}

// Unverified email: offer to send the link. Coming back from the link (?verified=1)
// shows a confirmation and refreshes the account so the banner disappears.
function VerifyEmailBanner({ user }) {
    const dispatch = useDispatch();
    const [params, setParams] = useSearchParams();
    const [sending, setSending] = useState(false);
    const [sent, setSent] = useState(null);

    useEffect(() => {
        if (params.get('verified') === '1') {
            dispatch(toastSuccess('Thanks — your email address is verified.'));
            dispatch(fetchMe());
            setParams({}, { replace: true });
        }
    }, [params, dispatch, setParams]);

    if (user.email_verified_at) return null;

    const send = async () => {
        setSending(true);
        try {
            const { data } = await api.post('/profile/verification-email');
            setSent(data.message);
        } catch (err) {
            dispatch(toastError(err.message));
        } finally {
            setSending(false);
        }
    };

    return (
        <div role="status" className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-warning bg-warning-bg p-4 text-sm text-warning">
            <span>{sent ?? `Your email address (${user.email}) isn't verified yet.`}</span>
            {!sent && (
                <button type="button" onClick={send} disabled={sending} className="rounded-md bg-surface px-3 py-1.5 font-medium text-warning shadow-sm hover:bg-bg disabled:opacity-50">
                    {sending ? 'Sending…' : 'Send verification email'}
                </button>
            )}
        </div>
    );
}

const MAX_BYTES = 2 * 1024 * 1024;
const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

// Profile photo: pick → preview → upload. The server checks the file again (type, size, real image).
function AvatarCard({ user }) {
    const dispatch = useDispatch();
    const inputRef = useRef(null);
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    // Free the preview's memory when it changes or the page closes.
    useEffect(() => () => preview && URL.revokeObjectURL(preview), [preview]);

    const choose = (e) => {
        const picked = e.target.files?.[0];
        e.target.value = ''; // allow picking the same file again
        setError(null);
        if (!picked) return;
        if (!TYPES.includes(picked.type)) return setError('Please choose a JPG, PNG or WebP photo.');
        if (picked.size > MAX_BYTES) return setError('The photo can be at most 2 MB.');
        setFile(picked);
        setPreview(URL.createObjectURL(picked));
    };

    const cancel = () => {
        setFile(null);
        setPreview(null);
    };

    const upload = async () => {
        setBusy(true);
        setError(null);
        try {
            const body = new FormData();
            body.append('avatar', file);
            const { data } = await api.post('/profile/avatar', body);
            dispatch(userUpdated(data.data)); // menu picture updates straight away
            dispatch(toastSuccess(data.message));
            cancel();
        } catch (err) {
            setError(err.errors?.avatar?.[0] ?? err.message);
        } finally {
            setBusy(false);
        }
    };

    const remove = async () => {
        setBusy(true);
        try {
            const { data } = await api.delete('/profile/avatar');
            dispatch(userUpdated(data.data));
            dispatch(toastSuccess(data.message));
        } catch (err) {
            setError(err.message);
        } finally {
            setBusy(false);
        }
    };

    return (
        <Card title="Profile photo" description="JPG, PNG or WebP, up to 2 MB. Shown in the menu and to HR.">
            <div className="flex flex-wrap items-center gap-6">
                {preview ? (
                    <img src={preview} alt="Preview of your new photo" className="h-20 w-20 rounded-full object-cover ring-2 ring-accent" />
                ) : (
                    <Avatar user={user} size="lg" />
                )}

                <div className="flex flex-wrap gap-2">
                    <input ref={inputRef} type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" id="avatar-input" onChange={choose} />
                    {file ? (
                        <>
                            <PrimaryButton type="button" onClick={upload} disabled={busy}>{busy ? 'Uploading…' : 'Save photo'}</PrimaryButton>
                            <SecondaryButton type="button" onClick={cancel} disabled={busy}>Cancel</SecondaryButton>
                        </>
                    ) : (
                        <>
                            <SecondaryButton type="button" onClick={() => inputRef.current?.click()} disabled={busy}>
                                {user.avatar_url ? 'Change photo' : 'Upload photo'}
                            </SecondaryButton>
                            {user.avatar_url && (
                                <button type="button" onClick={remove} disabled={busy} className="px-3 text-sm text-danger hover:underline disabled:opacity-50">
                                    Remove
                                </button>
                            )}
                        </>
                    )}
                </div>
            </div>
            {error && <p role="alert" className="mt-3 text-sm text-danger">{error}</p>}
        </Card>
    );
}

function ProfileForm({ user }) {
    const dispatch = useDispatch();
    const form = useApiForm({ name: user.name, email: user.email });

    const submit = (e) => {
        e.preventDefault();
        form.submit(
            async (data) => (await api.put('/profile', data)).data.data,
            {
                onSuccess: (saved) => {
                    dispatch(userUpdated(saved)); // menu shows the new name straight away
                    dispatch(toastSuccess('Profile saved.'));
                },
            },
        );
    };

    return (
        <Card title="Name and email" description="Changing your email means it has to be verified again.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <div>
                    <InputLabel htmlFor="name" value="Name" />
                    <TextInput id="name" className="mt-1 block w-full" value={form.data.name} onChange={form.field('name')} required autoComplete="name" />
                    <InputError message={form.errors.name} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput id="email" type="email" className="mt-1 block w-full" value={form.data.email} onChange={form.field('email')} required autoComplete="email" />
                    <InputError message={form.errors.email} className="mt-1" />
                </div>
                <div className="flex justify-end">
                    <PrimaryButton disabled={form.processing}>{form.processing ? 'Saving…' : 'Save'}</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}

// The password form has its own page (ChangePassword.jsx); here is just the link to it.
function PasswordLink() {
    return (
        <Card>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="text-lg font-medium text-content">Password</h3>
                    <p className="mt-1 text-sm text-muted">Change the password you sign in with.</p>
                </div>
                <Link to="/profile/password" className="rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium text-content hover:bg-bg">
                    Change password
                </Link>
            </div>
        </Card>
    );
}
