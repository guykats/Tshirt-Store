import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Login() {
    const { t } = useTranslation();
    const { login } = useAuth();
    const navigate = useNavigate();

    useDocumentMeta(t('meta_login_title', { app: t('app_name') }));

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            await login(email, password);
            navigate('/dashboard');
        } catch {
            setError(t('login_error'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto max-w-sm px-6 py-16">
            <h1 className="mb-6 font-serif text-2xl">{t('login_title')}</h1>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="mb-1 block text-sm">{t('email')}</label>
                    <input
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm">{t('password')}</label>
                    <input
                        type="password"
                        required
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                {error && <p className="text-sm text-red-700">{error}</p>}
                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {t('login_button')}
                </button>
            </form>
        </div>
    );
}
