import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useDispatch, useSelector } from 'react-redux';
import api from '../api/client';
import Page, { Card } from '../components/Page';
import useApiForm from '../hooks/useApiForm';
import { selectAuth, userUpdated } from '../store/authSlice';
import { toastSuccess } from '../store/toastSlice';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

export default function Profile() {
    const { user, employee } = useSelector(selectAuth);

    return (
        <Page title="Profile" width="max-w-3xl">
            <Card title="Your account">
                <dl className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <Item label="Role">{ROLE_LABELS[user.role]}</Item>
                    <Item label="Employee ID">{employee?.employee_code ?? '—'}</Item>
                    <Item label="Department">{employee?.department?.name ?? '—'}</Item>
                    <Item label="Rank">{employee?.rank_label ?? '—'}</Item>
                </dl>
                <p className="mt-4 text-xs text-muted">Role, department and rank are managed by HR.</p>
            </Card>

            <ProfileForm user={user} />
            <PasswordForm />
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

function PasswordForm() {
    const dispatch = useDispatch();
    const empty = { current_password: '', password: '', password_confirmation: '' };
    const form = useApiForm(empty);

    const submit = (e) => {
        e.preventDefault();
        form.submit((data) => api.put('/profile/password', data), {
            onSuccess: () => {
                form.reset(empty);
                dispatch(toastSuccess('Password changed.'));
            },
            onError: () => form.setData({ password: '', password_confirmation: '' }),
        });
    };

    return (
        <Card title="Change password" description="Use at least 8 characters.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <div>
                    <InputLabel htmlFor="current_password" value="Current password" />
                    <TextInput id="current_password" type="password" className="mt-1 block w-full" value={form.data.current_password} onChange={form.field('current_password')} autoComplete="current-password" />
                    <InputError message={form.errors.current_password} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="password" value="New password" />
                    <TextInput id="password" type="password" className="mt-1 block w-full" value={form.data.password} onChange={form.field('password')} autoComplete="new-password" />
                    <InputError message={form.errors.password} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirm new password" />
                    <TextInput id="password_confirmation" type="password" className="mt-1 block w-full" value={form.data.password_confirmation} onChange={form.field('password_confirmation')} autoComplete="new-password" />
                </div>
                <div className="flex justify-end">
                    <PrimaryButton disabled={form.processing}>{form.processing ? 'Saving…' : 'Change password'}</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}
