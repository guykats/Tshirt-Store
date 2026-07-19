import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function AccountSettings() {
    const { t } = useTranslation();
    const { changePassword } = useAuth();

    useDocumentMeta(t('meta_account_settings_title', { app: t('app_name') }));

    const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    function update(field) {
        return (e) => setForm((f) => ({ ...f, [field]: e.target.value }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setSuccess(false);
        setSubmitting(true);
        try {
            await changePassword(form);
            setSuccess(true);
            setForm({ current_password: '', password: '', password_confirmation: '' });
        } catch (err) {
            if (err.response?.data?.errors?.current_password) {
                setError(t('account_change_password_wrong_current'));
            } else {
                setError(t('account_change_password_error'));
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto max-w-sm px-6 py-16">
            <h1 className="mb-6 font-serif text-2xl">{t('account_settings_title')}</h1>
            <h2 className="mb-4 text-lg">{t('account_change_password_title')}</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="account-current-password" className="mb-1 block text-sm">{t('account_current_password')}</label>
                    <input
                        id="account-current-password"
                        type="password"
                        required
                        value={form.current_password}
                        onChange={update('current_password')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="account-new-password" className="mb-1 block text-sm">{t('account_new_password')}</label>
                    <input
                        id="account-new-password"
                        type="password"
                        required
                        minLength={8}
                        value={form.password}
                        onChange={update('password')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="account-new-password-confirmation" className="mb-1 block text-sm">{t('confirm_password')}</label>
                    <input
                        id="account-new-password-confirmation"
                        type="password"
                        required
                        minLength={8}
                        value={form.password_confirmation}
                        onChange={update('password_confirmation')}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
                {success && <p role="alert" className="text-sm text-green-700">{t('account_change_password_success')}</p>}
                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {t('account_change_password_button')}
                </button>
            </form>
        </div>
    );
}
