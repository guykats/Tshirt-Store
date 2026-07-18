import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function ForgotPassword() {
    const { t } = useTranslation();
    const { requestPasswordReset } = useAuth();

    useDocumentMeta(t('meta_forgot_password_title', { app: t('app_name') }));

    const [email, setEmail] = useState('');
    const [error, setError] = useState(null);
    const [submitted, setSubmitted] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            await requestPasswordReset(email);
            setSubmitted(true);
        } catch {
            setError(t('forgot_password_error'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto max-w-sm px-6 py-16">
            <h1 className="mb-6 font-serif text-2xl">{t('forgot_password_title')}</h1>
            {submitted ? (
                <p role="status" className="text-sm">{t('forgot_password_sent')}</p>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <p className="text-sm text-ink-soft">{t('forgot_password_hint')}</p>
                    <div>
                        <label htmlFor="forgot-password-email" className="mb-1 block text-sm">{t('email')}</label>
                        <input
                            id="forgot-password-email"
                            type="email"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>
                    {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
                    <button
                        type="submit"
                        disabled={submitting}
                        className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                    >
                        {t('forgot_password_button')}
                    </button>
                </form>
            )}
            <p className="mt-4 text-sm">
                <Link to="/login" className="underline">{t('forgot_password_back_to_login')}</Link>
            </p>
        </div>
    );
}
