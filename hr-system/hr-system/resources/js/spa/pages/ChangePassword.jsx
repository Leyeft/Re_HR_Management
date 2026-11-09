// /profile/password (everyone): change your own password. Linked from the Profile page.
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useDispatch } from 'react-redux';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/client';
import Page, { Card } from '../components/Page';
import useApiForm from '../hooks/useApiForm';
import { toastSuccess } from '../store/toastSlice';

export default function ChangePassword() {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const empty = { current_password: '', password: '', password_confirmation: '' };
    const form = useApiForm(empty);

    const submit = (e) => {
        e.preventDefault();
        form.submit((data) => api.put('/profile/password', data), {
            onSuccess: () => {
                dispatch(toastSuccess('Password changed.'));
                navigate('/profile');
            },
            onError: () => form.setData({ password: '', password_confirmation: '' }),
        });
    };

    return (
        <Page title="Change password" width="max-w-3xl">
            <Card description="Enter your current password, then the new one twice. Use at least 8 characters.">
                <form onSubmit={submit} className="space-y-4" noValidate>
                    <div>
                        <InputLabel htmlFor="current_password" value="Current password" />
                        <TextInput id="current_password" type="password" className="mt-1 block w-full" value={form.data.current_password} onChange={form.field('current_password')} autoComplete="current-password" autoFocus />
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
                    <div className="flex justify-end gap-3">
                        <Link to="/profile" className="rounded-md border border-line bg-surface px-4 py-2 text-sm hover:bg-bg">
                            Cancel
                        </Link>
                        <PrimaryButton disabled={form.processing}>{form.processing ? 'Saving…' : 'Change password'}</PrimaryButton>
                    </div>
                </form>
            </Card>
        </Page>
    );
}
