import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';

export default function Register() {
    const { t } = useTranslation();
    const { register } = useAuth();
    const navigate = useNavigate();
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
            <h1 className="mb-6 text-2xl font-semibold">{t('register_title')}</h1>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="mb-1 block text-sm">{t('name')}</label>
                    <input
                        required
                        value={form.name}
                        onChange={update('name')}
                        className="w-full rounded border border-neutral-300 px-3 py-2"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm">{t('email')}</label>
                    <input
                        type="email"
                        required
                        value={form.email}
                        onChange={update('email')}
                        className="w-full rounded border border-neutral-300 px-3 py-2"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm">{t('password')}</label>
                    <input
                        type="password"
                        required
                        minLength={8}
                        value={form.password}
                        onChange={update('password')}
                        className="w-full rounded border border-neutral-300 px-3 py-2"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm">{t('confirm_password')}</label>
                    <input
                        type="password"
                        required
                        minLength={8}
                        value={form.password_confirmation}
                        onChange={update('password_confirmation')}
                        className="w-full rounded border border-neutral-300 px-3 py-2"
                    />
                </div>
                {error && <p className="text-sm text-red-600">{error}</p>}
                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-neutral-900 px-4 py-2 text-white disabled:opacity-50"
                >
                    {t('register_button')}
                </button>
            </form>
        </div>
    );
}
