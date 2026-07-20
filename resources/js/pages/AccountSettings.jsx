import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function AccountSettings() {
    const { t } = useTranslation();
    const { changePassword, deleteAccount } = useAuth();
    const navigate = useNavigate();

    useDocumentMeta(t('meta_account_settings_title', { app: t('app_name') }));

    const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const [deletePassword, setDeletePassword] = useState('');
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleteError, setDeleteError] = useState(null);
    const [deleting, setDeleting] = useState(false);

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

    async function handleDeleteAccount(e) {
        e.preventDefault();
        setDeleteError(null);
        setDeleting(true);
        try {
            await deleteAccount(deletePassword);
            navigate('/');
        } catch (err) {
            if (err.response?.data?.errors?.current_password) {
                setDeleteError(t('account_delete_wrong_current'));
            } else {
                setDeleteError(t('account_delete_error'));
            }
        } finally {
            setDeleting(false);
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

            <div className="mt-12 rounded border border-red-200 bg-red-50 p-4">
                <h2 className="mb-2 text-lg text-red-800">{t('account_delete_title')}</h2>
                <p className="mb-4 text-sm text-red-900">{t('account_delete_warning')}</p>
                <form onSubmit={handleDeleteAccount} className="space-y-4">
                    <div>
                        <label htmlFor="account-delete-password" className="mb-1 block text-sm">{t('account_current_password')}</label>
                        <input
                            id="account-delete-password"
                            type="password"
                            required
                            value={deletePassword}
                            onChange={(e) => setDeletePassword(e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>
                    {deleteError && <p role="alert" className="text-sm text-red-700">{deleteError}</p>}
                    {!confirmingDelete ? (
                        <button
                            type="button"
                            onClick={() => setConfirmingDelete(true)}
                            disabled={!deletePassword}
                            className="w-full rounded border border-red-700 px-4 py-2.5 text-sm tracking-wide text-red-700 uppercase hover:bg-red-100 disabled:opacity-50"
                        >
                            {t('account_delete_button')}
                        </button>
                    ) : (
                        <div className="rounded border border-red-300 bg-red-100 p-3">
                            <p className="text-sm text-red-900">{t('account_delete_confirm_prompt')}</p>
                            <div className="mt-2 flex items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={deleting}
                                    className="rounded bg-red-700 px-3 py-1.5 text-sm text-white disabled:opacity-60"
                                >
                                    {deleting ? t('account_delete_in_progress') : t('account_delete_confirm_yes')}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirmingDelete(false)}
                                    disabled={deleting}
                                    className="text-sm text-ink-soft hover:underline"
                                >
                                    {t('account_delete_confirm_no')}
                                </button>
                            </div>
                        </div>
                    )}
                </form>
            </div>
        </div>
    );
}
