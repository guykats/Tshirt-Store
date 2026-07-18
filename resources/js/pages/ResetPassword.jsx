import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function ResetPassword() {
    const { t } = useTranslation();
    const { resetPassword } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();

    useDocumentMeta(t('meta_reset_password_title', { app: t('app_name') }));

    const token = searchParams.get('token') || '';
    const email = searchParams.get('email') || '';

    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            await resetPassword({
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            navigate('/login');
        } catch {
            setError(t('reset_password_error'));
        } finally {
            setSubmitting(false);
        }
    }

    if (!token || !email) {
        return (
            <div className="mx-auto max-w-sm px-6 py-16">
                <h1 className="mb-6 font-serif text-2xl">{t('reset_password_title')}</h1>
                <p role="alert" className="text-sm text-red-700">{t('reset_password_invalid_link')}</p>
                <p className="mt-4 text-sm">
                    <Link to="/forgot-password" className="underline">{t('forgot_password_title')}</Link>
                </p>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-sm px-6 py-16">
            <h1 className="mb-6 font-serif text-2xl">{t('reset_password_title')}</h1>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="reset-password-password" className="mb-1 block text-sm">{t('reset_password_new_password')}</label>
                    <input
                        id="reset-password-password"
                        type="password"
                        required
                        minLength={8}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="reset-password-password-confirmation" className="mb-1 block text-sm">{t('confirm_password')}</label>
                    <input
                        id="reset-password-password-confirmation"
                        type="password"
                        required
                        minLength={8}
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {t('reset_password_button')}
                </button>
            </form>
        </div>
    );
}
