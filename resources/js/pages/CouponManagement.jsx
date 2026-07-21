import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const EMPTY_COUPON_FORM = {
    code: '',
    type: 'percent',
    value: '',
    expires_at: '',
    max_redemptions: '',
    active: true,
};

// Admin coupon-code management: CouponService/Coupon already validate and
// redeem codes at checkout (expiration, usage limits) — this is the missing
// other half, following the same paginated-list + create/edit-form shape as
// AdminReviews.jsx (search + pagination) and ProductManagement.jsx (the
// create/edit form itself). There's no destroy endpoint: a coupon that's
// already been redeemed needs to stay around as a record for the orders that
// used it, so "deactivate" (active=false) is the supported way to retire one,
// same as products use status=archived instead of hard deletion.
export default function CouponManagement() {
    const { t } = useTranslation();
    const [searchParams, setSearchParams] = useSearchParams();

    useDocumentMeta(t('meta_coupon_management_title', { app: t('app_name') }));

    const [coupons, setCoupons] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(null);
    const [searchInput, setSearchInput] = useState(searchParams.get('search') || '');

    const [editingCouponId, setEditingCouponId] = useState(null); // id | 'new' | null
    const [form, setForm] = useState(EMPTY_COUPON_FORM);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState(null); // 'saved' | 'error' | null
    const [fieldErrors, setFieldErrors] = useState({});
    const [togglingId, setTogglingId] = useState(null);

    const page = Number(searchParams.get('page')) || 1;
    const search = searchParams.get('search') || '';

    useEffect(() => {
        loadCoupons();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [page, search]);

    function loadCoupons() {
        setLoading(true);
        setLoadError(null);
        return api.get('/api/admin/coupons', { params: { page, search: search || undefined } })
            .then((res) => {
                setCoupons(res.data.data);
                setMeta(res.data.meta);
            })
            .catch(() => setLoadError(t('coupon_management_load_error')))
            .finally(() => setLoading(false));
    }

    function goToPage(nextPage) {
        const params = {};
        if (search) params.search = search;
        if (nextPage > 1) params.page = String(nextPage);
        setSearchParams(params);
    }

    function handleSearchSubmit(e) {
        e.preventDefault();
        const params = {};
        if (searchInput.trim()) params.search = searchInput.trim();
        setSearchParams(params);
    }

    function startNewCoupon() {
        setEditingCouponId('new');
        setStatus(null);
        setFieldErrors({});
        setForm(EMPTY_COUPON_FORM);
    }

    function startEditCoupon(coupon) {
        setEditingCouponId(coupon.id);
        setStatus(null);
        setFieldErrors({});
        setForm({
            code: coupon.code,
            type: coupon.type,
            value: coupon.value,
            expires_at: coupon.expires_at ? coupon.expires_at.slice(0, 10) : '',
            max_redemptions: coupon.max_redemptions ?? '',
            active: coupon.active,
        });
    }

    function cancelEdit() {
        setEditingCouponId(null);
        setStatus(null);
        setFieldErrors({});
        setForm(EMPTY_COUPON_FORM);
    }

    function updateField(field, value) {
        setForm((prev) => ({ ...prev, [field]: value }));
    }

    function buildPayload(source) {
        return {
            code: source.code,
            type: source.type,
            value: Number(source.value),
            expires_at: source.expires_at ? source.expires_at : null,
            max_redemptions: source.max_redemptions === '' || source.max_redemptions === null
                ? null
                : Number(source.max_redemptions),
            active: !!source.active,
        };
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setStatus(null);
        setFieldErrors({});
        setSaving(true);
        try {
            const payload = buildPayload(form);
            if (editingCouponId === 'new') {
                await api.post('/api/admin/coupons', payload);
            } else {
                await api.put(`/api/admin/coupons/${editingCouponId}`, payload);
            }
            await loadCoupons();
            setEditingCouponId(null);
            setForm(EMPTY_COUPON_FORM);
            setStatus('saved');
        } catch (err) {
            setStatus('error');
            setFieldErrors(err?.response?.data?.errors || {});
        } finally {
            setSaving(false);
        }
    }

    async function handleToggleActive(coupon) {
        setTogglingId(coupon.id);
        setStatus(null);
        try {
            await api.put(`/api/admin/coupons/${coupon.id}`, buildPayload({
                code: coupon.code,
                type: coupon.type,
                value: coupon.value,
                expires_at: coupon.expires_at ? coupon.expires_at.slice(0, 10) : '',
                max_redemptions: coupon.max_redemptions,
                active: !coupon.active,
            }));
            await loadCoupons();
        } catch {
            setStatus('error');
        } finally {
            setTogglingId(null);
        }
    }

    return (
        <div className="max-w-4xl">
            <h1 className="mb-2 font-serif text-2xl">{t('coupon_management_title')}</h1>
            <p className="mb-6 max-w-2xl text-sm text-ink-soft">{t('coupon_management_hint')}</p>

            {loadError && (
                <p role="alert" className="mb-4 text-sm text-red-700">{loadError}</p>
            )}
            {status === 'saved' && (
                <p role="status" className="mb-4 text-sm text-green-700">{t('coupon_management_saved')}</p>
            )}
            {status === 'error' && (
                <p role="alert" className="mb-4 text-sm text-red-700">{t('coupon_management_error')}</p>
            )}

            <form onSubmit={handleSearchSubmit} className="mb-6 flex items-end gap-3">
                <div>
                    <label htmlFor="coupon-search" className="mb-1 block text-sm">
                        {t('coupon_management_search_label')}
                    </label>
                    <input
                        id="coupon-search"
                        type="text"
                        value={searchInput}
                        onChange={(e) => setSearchInput(e.target.value)}
                        placeholder={t('coupon_management_search_placeholder')}
                        className="w-64 rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <button
                    type="submit"
                    className="rounded border border-ink px-4 py-2 text-sm tracking-wide uppercase hover:bg-parchment-dim"
                >
                    {t('coupon_management_search_button')}
                </button>
            </form>

            {loading ? (
                <p className="text-ink-soft">…</p>
            ) : (
                <div className="mb-8 overflow-x-auto rounded border border-line">
                    <table className="w-full text-sm">
                        <thead className="bg-parchment-dim text-left">
                            <tr>
                                <th className="px-4 py-2">{t('coupon_management_col_code')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_type')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_value')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_expires')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_redemptions')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_status')}</th>
                                <th className="px-4 py-2">{t('coupon_management_col_actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {coupons.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="px-4 py-6 text-center text-ink-soft">
                                        {t('coupon_management_empty')}
                                    </td>
                                </tr>
                            )}
                            {coupons.map((coupon) => (
                                <tr key={coupon.id} className="border-t border-line align-top">
                                    <td className="px-4 py-3 font-medium">{coupon.code}</td>
                                    <td className="px-4 py-3">{t(`coupon_management_type_${coupon.type}`)}</td>
                                    <td className="px-4 py-3">
                                        {coupon.type === 'percent' ? `${coupon.value}%` : `$${coupon.value}`}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-xs text-ink-soft">
                                        {coupon.expires_at
                                            ? new Date(coupon.expires_at).toLocaleDateString()
                                            : t('coupon_management_no_expiry')}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-xs text-ink-soft">
                                        {coupon.redemptions_count}
                                        {coupon.max_redemptions !== null ? ` / ${coupon.max_redemptions}` : ''}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={coupon.active ? 'font-medium text-green-700' : 'font-medium text-ink-soft'}>
                                            {coupon.active ? t('coupon_management_status_active') : t('coupon_management_status_inactive')}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        <div className="flex flex-wrap gap-3">
                                            <button
                                                type="button"
                                                onClick={() => startEditCoupon(coupon)}
                                                className="text-sm underline"
                                            >
                                                {t('coupon_management_edit')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleToggleActive(coupon)}
                                                disabled={togglingId === coupon.id}
                                                aria-label={
                                                    coupon.active
                                                        ? t('coupon_management_deactivate_aria', { code: coupon.code })
                                                        : t('coupon_management_activate_aria', { code: coupon.code })
                                                }
                                                className="text-sm text-red-700 underline hover:text-red-800 disabled:opacity-50"
                                            >
                                                {coupon.active ? t('coupon_management_deactivate') : t('coupon_management_activate')}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {meta.last_page > 1 && (
                <div className="mb-8 flex items-center justify-center gap-4">
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page - 1)}
                        disabled={meta.current_page <= 1}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('coupon_management_previous')}
                    </button>
                    <span className="text-sm text-ink-soft">
                        {t('coupon_management_page_of', { current: meta.current_page, last: meta.last_page })}
                    </span>
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page + 1)}
                        disabled={meta.current_page >= meta.last_page}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('coupon_management_next')}
                    </button>
                </div>
            )}

            {editingCouponId === null && (
                <button
                    type="button"
                    onClick={startNewCoupon}
                    className="rounded border border-ink px-5 py-2.5 text-sm tracking-wide uppercase hover:bg-parchment-dim"
                >
                    {t('coupon_management_add')}
                </button>
            )}

            {editingCouponId !== null && (
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4 rounded border border-line p-5">
                    <h3 className="font-serif text-base">
                        {editingCouponId === 'new' ? t('coupon_management_new_title') : t('coupon_management_edit')}
                    </h3>
                    <div>
                        <label htmlFor="coupon-code" className="mb-1 block text-sm">
                            {t('coupon_management_code_label')}
                        </label>
                        <input
                            id="coupon-code"
                            type="text"
                            required
                            maxLength={50}
                            value={form.code}
                            onChange={(e) => updateField('code', e.target.value.toUpperCase())}
                            className="w-full rounded border border-line bg-parchment px-3 py-2 uppercase"
                        />
                        {fieldErrors.code && (
                            <p role="alert" className="mt-1 text-xs text-red-700">{fieldErrors.code[0]}</p>
                        )}
                        <p className="mt-1 text-xs text-ink-soft">{t('coupon_management_code_hint')}</p>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="coupon-type" className="mb-1 block text-sm">
                                {t('coupon_management_type_label')}
                            </label>
                            <select
                                id="coupon-type"
                                value={form.type}
                                onChange={(e) => updateField('type', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            >
                                <option value="percent">{t('coupon_management_type_percent')}</option>
                                <option value="fixed">{t('coupon_management_type_fixed')}</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="coupon-value" className="mb-1 block text-sm">
                                {form.type === 'percent'
                                    ? t('coupon_management_value_percent_label')
                                    : t('coupon_management_value_fixed_label')}
                            </label>
                            <input
                                id="coupon-value"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                value={form.value}
                                onChange={(e) => updateField('value', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                            {fieldErrors.value && (
                                <p role="alert" className="mt-1 text-xs text-red-700">{fieldErrors.value[0]}</p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="coupon-expires" className="mb-1 block text-sm">
                                {t('coupon_management_expires_label')}
                            </label>
                            <input
                                id="coupon-expires"
                                type="date"
                                value={form.expires_at}
                                onChange={(e) => updateField('expires_at', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                            <p className="mt-1 text-xs text-ink-soft">{t('coupon_management_expires_hint')}</p>
                        </div>
                        <div>
                            <label htmlFor="coupon-max-redemptions" className="mb-1 block text-sm">
                                {t('coupon_management_max_redemptions_label')}
                            </label>
                            <input
                                id="coupon-max-redemptions"
                                type="number"
                                min="1"
                                value={form.max_redemptions}
                                onChange={(e) => updateField('max_redemptions', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                            <p className="mt-1 text-xs text-ink-soft">{t('coupon_management_max_redemptions_hint')}</p>
                        </div>
                    </div>
                    <div>
                        <label htmlFor="coupon-active" className="flex items-center gap-2 text-sm">
                            <input
                                id="coupon-active"
                                type="checkbox"
                                checked={form.active}
                                onChange={(e) => updateField('active', e.target.checked)}
                                className="h-4 w-4 rounded border-line"
                            />
                            {t('coupon_management_active_label')}
                        </label>
                    </div>
                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={saving}
                            className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                        >
                            {t('coupon_management_save')}
                        </button>
                        <button type="button" onClick={cancelEdit} className="text-sm underline">
                            {t('coupon_management_cancel')}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}
