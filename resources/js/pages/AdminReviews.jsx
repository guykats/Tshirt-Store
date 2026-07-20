import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

// Admin-only review moderation panel — lists every review across every
// product (unlike the public per-product listing on ProductDetail.jsx) so an
// admin can find and remove an abusive/fake one without already knowing which
// product it's on. Follows the same paginated-table shape as AuditLog.jsx
// (which itself follows ProjectProgress.jsx) so this fits existing conventions
// rather than inventing a new one.
export default function AdminReviews() {
    const { t } = useTranslation();
    const [searchParams, setSearchParams] = useSearchParams();
    const [reviews, setReviews] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState(null);

    const page = Number(searchParams.get('page')) || 1;

    useDocumentMeta(t('meta_admin_reviews_title', { app: t('app_name') }));

    useEffect(() => {
        loadReviews();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [page]);

    function loadReviews() {
        setLoading(true);
        setError(null);
        return api.get('/api/admin/reviews', { params: { page } })
            .then((res) => {
                setReviews(res.data.data);
                setMeta(res.data.meta);
            })
            .catch(() => setError(t('admin_reviews_error')))
            .finally(() => setLoading(false));
    }

    function goToPage(nextPage) {
        const params = { page: String(nextPage) };
        if (nextPage <= 1) delete params.page;
        setSearchParams(params);
    }

    async function handleDelete(review) {
        setDeleteError(null);
        try {
            await api.delete(`/api/products/${review.product_slug}/reviews/${review.id}`);
            setConfirmDeleteId(null);
            await loadReviews();
        } catch {
            setConfirmDeleteId(null);
            setDeleteError(t('admin_reviews_delete_error'));
        }
    }

    return (
        <div className="mx-auto max-w-5xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('admin_reviews_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('admin_reviews_hint')}</p>

            {error && (
                <p role="alert" className="mb-4 text-sm text-red-700">
                    {error}
                </p>
            )}
            {deleteError && (
                <p role="alert" className="mb-4 text-sm text-red-700">
                    {deleteError}
                </p>
            )}

            <div className="overflow-x-auto rounded border border-line">
                <table className="w-full text-sm">
                    <thead className="bg-parchment-dim text-left">
                        <tr>
                            <th className="px-4 py-2">{t('admin_reviews_col_product')}</th>
                            <th className="px-4 py-2">{t('admin_reviews_col_reviewer')}</th>
                            <th className="px-4 py-2">{t('admin_reviews_col_rating')}</th>
                            <th className="px-4 py-2">{t('admin_reviews_col_body')}</th>
                            <th className="px-4 py-2">{t('admin_reviews_col_date')}</th>
                            <th className="px-4 py-2">{t('admin_reviews_col_actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!loading && reviews.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-6 text-center text-ink-soft">
                                    {t('admin_reviews_empty')}
                                </td>
                            </tr>
                        )}
                        {reviews.map((review) => (
                            <tr key={review.id} className="border-t border-line align-top">
                                <td className="px-4 py-3">{review.product_name}</td>
                                <td className="px-4 py-3 whitespace-nowrap">{review.reviewer_name}</td>
                                <td className="px-4 py-3">
                                    {t('admin_reviews_stars', { rating: review.rating })}
                                </td>
                                <td className="px-4 py-3 max-w-xs">
                                    {review.body || <span className="text-ink-soft">—</span>}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap text-xs text-ink-soft">
                                    {review.created_at ? new Date(review.created_at).toLocaleString() : '—'}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {confirmDeleteId === review.id ? (
                                        <span className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(review)}
                                                className="font-medium text-red-700 underline"
                                            >
                                                {t('admin_reviews_confirm_delete_yes')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setConfirmDeleteId(null)}
                                                className="underline"
                                            >
                                                {t('admin_reviews_confirm_delete_no')}
                                            </button>
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => setConfirmDeleteId(review.id)}
                                            aria-label={t('admin_reviews_delete_aria', { reviewer: review.reviewer_name, product: review.product_name })}
                                            className="text-red-700 underline hover:text-red-800"
                                        >
                                            {t('admin_reviews_delete')}
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {meta.last_page > 1 && (
                <div className="mt-6 flex items-center justify-center gap-4">
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page - 1)}
                        disabled={meta.current_page <= 1}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('admin_reviews_previous')}
                    </button>
                    <span className="text-sm text-ink-soft">
                        {t('admin_reviews_page_of', { current: meta.current_page, last: meta.last_page })}
                    </span>
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page + 1)}
                        disabled={meta.current_page >= meta.last_page}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('admin_reviews_next')}
                    </button>
                </div>
            )}
        </div>
    );
}
