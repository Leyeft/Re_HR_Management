// Forgot-password page: asks for the email and sends a reset link (same answer whether or not the account exists).
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useState } from 'react';
import api from '../../api/client';
import useApiForm from '../../hooks/useApiForm';
import GuestCard from '../../layouts/GuestCard';

export default function ForgotPassword() {
    const form = useApiForm({ email: '' });
    const [sent, setSent] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        form.submit((data) => api.post('/auth/forgot-password', data), {
            onSuccess: (res) => setSent(res.data.message),
        });
    };

    return (
        <GuestCard title="Forgot your password?" intro="Enter your work email and we'll send you a link to choose a new one.">
            {sent ? (
                <p role="status" className="rounded-md bg-success-bg p-4 text-sm text-success">{sent}</p>
            ) : (
                <form onSubmit={submit} className="space-y-4" noValidate>
                    <div>
                        <InputLabel htmlFor="email" value="Work email" />
                        <TextInput id="email" type="email" className="mt-1 block w-full" value={form.data.email} onChange={form.field('email')} autoComplete="username" autoFocus />
                        <InputError message={form.errors.email} className="mt-1" />
                    </div>
                    <PrimaryButton className="w-full justify-center" disabled={form.processing}>
                        {form.processing ? 'Sending…' : 'Email me a reset link'}
                    </PrimaryButton>
                </form>
            )}
        </GuestCard>
    );
}
