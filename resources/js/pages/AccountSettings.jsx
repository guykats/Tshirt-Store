import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';
import api from '../lib/api';

const EMPTY_ADDRESS_FORM = {
    full_name: '', line1: '', line2: '', city: '', state: '', postal_code: '', country: 'US', phone: '',
};

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

    const [addresses, setAddresses] = useState([]);
    const [addressForm, setAddressForm] = useState(EMPTY_ADDRESS_FORM);
    const [editingAddressId, setEditingAddressId] = useState(null);
    const [showAddressForm, setShowAddressForm] = useState(false);
    const [addressError, setAddressError] = useState(null);
    const [addressSaving, setAddressSaving] = useState(false);
    const [confirmingDeleteAddressId, setConfirmingDeleteAddressId] = useState(null);
    const [addressActionError, setAddressActionError] = useState(null);

    useEffect(() => {
        api.get('/api/account/addresses').then((res) => setAddresses(res.data.data));
    }, []);

    function update(field) {
        return (e) => setForm((f) => ({ ...f, [field]: e.target.value }));
    }

    function updateAddressField(field) {
        return (e) => setAddressForm((f) => ({ ...f, [field]: e.target.value }));
    }

    function startAddAddress() {
        setEditingAddressId(null);
        setAddressForm(EMPTY_ADDRESS_FORM);
        setAddressError(null);
        setShowAddressForm(true);
    }

    function startEditAddress(address) {
        setEditingAddressId(address.id);
        setAddressForm({
            full_name: address.full_name,
            line1: address.line1,
            line2: address.line2 || '',
            city: address.city,
            state: address.state,
            postal_code: address.postal_code,
            country: address.country || 'US',
            phone: address.phone || '',
        });
        setAddressError(null);
        setShowAddressForm(true);
    }

    function cancelAddressForm() {
        setShowAddressForm(false);
        setEditingAddressId(null);
        setAddressForm(EMPTY_ADDRESS_FORM);
        setAddressError(null);
    }

    async function handleAddressSubmit(e) {
        e.preventDefault();
        setAddressError(null);
        setAddressSaving(true);
        try {
            if (editingAddressId) {
                const res = await api.put(`/api/account/addresses/${editingAddressId}`, addressForm);
                setAddresses((list) => list.map((a) => (a.id === editingAddressId ? res.data.data : a)));
            } else {
                const res = await api.post('/api/account/addresses', addressForm);
                setAddresses((list) => [...list, res.data.data]);
            }
            cancelAddressForm();
        } catch (err) {
            setAddressError(err.response?.data?.message || t('account_addresses_save_error'));
        } finally {
            setAddressSaving(false);
        }
    }

    async function handleSetDefaultAddress(id) {
        setAddressActionError(null);
        try {
            const res = await api.post(`/api/account/addresses/${id}/default`);
            setAddresses((list) => list.map((a) => (a.id === id ? res.data.data : { ...a, is_default: false })));
        } catch (err) {
            setAddressActionError(err.response?.data?.message || t('account_addresses_set_default_error'));
        }
    }

    async function handleDeleteAddress(id) {
        if (confirmingDeleteAddressId !== id) {
            setConfirmingDeleteAddressId(id);
            setAddressActionError(null);
            return;
        }
        setAddressActionError(null);
        try {
            await api.delete(`/api/account/addresses/${id}`);
            setAddresses((list) => list.filter((a) => a.id !== id));
        } catch (err) {
            setAddressActionError(err.response?.data?.message || t('account_addresses_delete_error'));
        } finally {
            setConfirmingDeleteAddressId(null);
        }
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
            } else if (err.response?.data?.errors?.account) {
                setDeleteError(t('account_delete_admin_blocked'));
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

            <div className="mt-12">
                <h2 className="mb-4 text-lg">{t('account_addresses_title')}</h2>

                {addresses.length === 0 && !showAddressForm && (
                    <p className="mb-4 text-sm text-ink-soft">{t('account_addresses_empty')}</p>
                )}

                {addressActionError && <p role="alert" className="mb-3 text-sm text-red-700">{addressActionError}</p>}

                <ul className="space-y-3">
                    {addresses.map((a) => (
                        <li key={a.id} className="rounded border border-line p-3">
                            <div className="flex items-start justify-between gap-2">
                                <div className="text-sm">
                                    <p className="font-medium">
                                        {a.full_name}
                                        {a.is_default && (
                                            <span className="ms-2 rounded bg-parchment-dim px-2 py-0.5 text-xs text-ink-soft uppercase">
                                                {t('account_addresses_default_badge')}
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-ink-soft">{a.line1}{a.line2 ? `, ${a.line2}` : ''}</p>
                                    <p className="text-ink-soft">{a.city}, {a.state} {a.postal_code}</p>
                                </div>
                            </div>
                            <div className="mt-2 flex flex-wrap items-center gap-3">
                                {!a.is_default && (
                                    <button
                                        type="button"
                                        onClick={() => handleSetDefaultAddress(a.id)}
                                        className="text-sm text-brass hover:underline"
                                    >
                                        {t('account_addresses_set_default_button')}
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => startEditAddress(a)}
                                    className="text-sm text-ink-soft hover:underline"
                                >
                                    {t('account_addresses_edit_button')}
                                </button>
                                {confirmingDeleteAddressId === a.id ? (
                                    <span className="flex items-center gap-2 text-sm">
                                        <span className="text-red-800">{t('account_addresses_delete_confirm_prompt')}</span>
                                        <button
                                            type="button"
                                            onClick={() => handleDeleteAddress(a.id)}
                                            className="text-red-700 hover:underline"
                                        >
                                            {t('account_addresses_delete_confirm_yes')}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setConfirmingDeleteAddressId(null)}
                                            className="text-ink-soft hover:underline"
                                        >
                                            {t('account_addresses_delete_confirm_no')}
                                        </button>
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => handleDeleteAddress(a.id)}
                                        className="text-sm text-red-700 hover:underline"
                                    >
                                        {t('account_addresses_delete_button')}
                                    </button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>

                {showAddressForm ? (
                    <form onSubmit={handleAddressSubmit} className="mt-4 space-y-4 rounded border border-line p-4">
                        <h3 className="text-sm font-medium">
                            {editingAddressId ? t('account_addresses_form_title_edit') : t('account_addresses_form_title_add')}
                        </h3>
                        {['full_name', 'line1', 'line2', 'city', 'state', 'postal_code', 'phone'].map((field) => (
                            <div key={field}>
                                <label htmlFor={`account-address-${field}`} className="mb-1 block text-sm">{t(`address_${field}`)}</label>
                                <input
                                    id={`account-address-${field}`}
                                    required={field !== 'line2' && field !== 'phone'}
                                    value={addressForm[field]}
                                    onChange={updateAddressField(field)}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2"
                                />
                            </div>
                        ))}
                        {addressError && <p role="alert" className="text-sm text-red-700">{addressError}</p>}
                        <div className="flex items-center gap-3">
                            <button
                                type="submit"
                                disabled={addressSaving}
                                className="rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                            >
                                {t('account_addresses_save_button')}
                            </button>
                            <button
                                type="button"
                                onClick={cancelAddressForm}
                                disabled={addressSaving}
                                className="text-sm text-ink-soft hover:underline"
                            >
                                {t('account_addresses_cancel_button')}
                            </button>
                        </div>
                    </form>
                ) : (
                    <button
                        type="button"
                        onClick={startAddAddress}
                        className="mt-4 w-full rounded border border-line px-4 py-2.5 text-sm tracking-wide text-ink uppercase hover:bg-parchment-dim"
                    >
                        {t('account_addresses_add_button')}
                    </button>
                )}
            </div>

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
