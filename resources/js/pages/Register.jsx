import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Register() {
    const { t } = useTranslation();
    const { register } = useAuth();
    const navigate = useNavigate();

    useDocumentMeta(t('meta_register_title', { app: t('app_name') }));

    const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    function update(field) {
        return (e) => setForm((f) => ({ ...f, [field]: e.target.value }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            await register(form);
            navigate('/');
        } catch (err) {
            setError(err.response?.data?.message || t('register_error'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto max-w-sm px-6 py-16">
            <h1 className="mb-6 font-serif text-2xl">{t('register_title')}</h1>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="register-name" className="mb-1 block text-sm">{t('name')}</label>
                    <input
                        id="register-name"
                        required
                        value={form.name}
                        onChange={update('name')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="register-email" className="mb-1 block text-sm">{t('email')}</label>
                    <input
                        id="register-email"
                        type="email"
                        required
                        value={form.email}
                        onChange={update('email')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="register-password" className="mb-1 block text-sm">{t('password')}</label>
                    <input
                        id="register-password"
                        type="password"
                        required
                        minLength={8}
                        value={form.password}
                        onChange={update('password')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="register-password-confirmation" className="mb-1 block text-sm">{t('confirm_password')}</label>
                    <input
                        id="register-password-confirmation"
                        type="password"
                        required
                        minLength={8}
                        value={form.password_confirmation}
                        onChange={update('password_confirmation')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {t('register_button')}
                </button>
            </form>
        </div>
    );
}
