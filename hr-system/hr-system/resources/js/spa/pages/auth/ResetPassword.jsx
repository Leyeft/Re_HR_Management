import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useDispatch } from 'react-redux';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import api from '../../api/client';
import useApiForm from '../../hooks/useApiForm';
import GuestCard from '../../layouts/GuestCard';
import { toastSuccess } from '../../store/toastSlice';

// Opened from the link in the reset email: /reset-password/{token}?email=…
export default function ResetPassword() {
    const { token } = useParams();
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const form = useApiForm({ email: params.get('email') ?? '', password: '', password_confirmation: '' });

    const submit = (e) => {
        e.preventDefault();
        form.submit((data) => api.post('/auth/reset-password', { ...data, token }), {
            onSuccess: (res) => {
                dispatch(toastSuccess(res.data.message));
                navigate('/login', { replace: true });
            },
        });
    };

    return (
        <GuestCard title="Choose a new password" intro="Use at least 8 characters.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <div>
                    <InputLabel htmlFor="email" value="Work email" />
                    <TextInput id="email" type="email" className="mt-1 block w-full" value={form.data.email} onChange={form.field('email')} autoComplete="username" />
                    <InputError message={form.errors.email} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="password" value="New password" />
                    <TextInput id="password" type="password" className="mt-1 block w-full" value={form.data.password} onChange={form.field('password')} autoComplete="new-password" autoFocus />
                    <InputError message={form.errors.password} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirm new password" />
                    <TextInput id="password_confirmation" type="password" className="mt-1 block w-full" value={form.data.password_confirmation} onChange={form.field('password_confirmation')} autoComplete="new-password" />
                </div>
                <PrimaryButton className="w-full justify-center" disabled={form.processing}>
                    {form.processing ? 'Saving…' : 'Set new password'}
                </PrimaryButton>
            </form>
        </GuestCard>
    );
}
