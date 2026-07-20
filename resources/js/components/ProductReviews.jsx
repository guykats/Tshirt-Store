import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../lib/AuthContext';
import StarRating from './StarRating';

export default function ProductReviews({ productSlug }) {
    const { t } = useTranslation();
    const { user } = useAuth();

    const [reviews, setReviews] = useState([]);
    const [meta, setMeta] = useState({ average_rating: null, count: 0 });
    const [loading, setLoading] = useState(true);

    const [eligibility, setEligibility] = useState(null);
    const [rating, setRating] = useState(0);
    const [body, setBody] = useState('');
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const [editingReviewId, setEditingReviewId] = useState(null);
    const [editRating, setEditRating] = useState(0);
    const [editBody, setEditBody] = useState('');
    const [editError, setEditError] = useState(null);
    const [editSubmitting, setEditSubmitting] = useState(false);

    const [confirmDeleteId, setConfirmDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState(null);

    function loadReviews() {
        return api.get(`/api/products/${productSlug}/reviews`).then((res) => {
            setReviews(res.data.data);
            setMeta(res.data.meta);
        });
    }

    useEffect(() => {
        setLoading(true);
        loadReviews().finally(() => setLoading(false));
    }, [productSlug]);

    useEffect(() => {
        if (!user) {
            setEligibility(null);
            return;
        }
        api.get(`/api/products/${productSlug}/reviews/eligibility`).then((res) => {
            setEligibility(res.data);
        });
    }, [productSlug, user]);

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);

        if (!rating) {
            setError(t('reviews_rating_required'));
            return;
        }

        setSubmitting(true);
        try {
            await api.post(`/api/products/${productSlug}/reviews`, { rating, body: body || null });
            setSuccess(true);
            setRating(0);
            setBody('');
            setEligibility((prev) => (prev ? { ...prev, can_review: false, already_reviewed: true } : prev));
            await loadReviews();
        } catch {
            setError(t('reviews_submit_error'));
        } finally {
            setSubmitting(false);
        }
    }

    function startEdit(review) {
        setEditingReviewId(review.id);
        setEditRating(review.rating);
        setEditBody(review.body || '');
        setEditError(null);
    }

    function cancelEdit() {
        setEditingReviewId(null);
        setEditError(null);
    }

    async function handleEditSubmit(e, reviewId) {
        e.preventDefault();
        setEditError(null);

        if (!editRating) {
            setEditError(t('reviews_rating_required'));
            return;
        }

        setEditSubmitting(true);
        try {
            await api.patch(`/api/products/${productSlug}/reviews/${reviewId}`, {
                rating: editRating,
                body: editBody || null,
            });
            setEditingReviewId(null);
            await loadReviews();
        } catch {
            setEditError(t('reviews_edit_error'));
        } finally {
            setEditSubmitting(false);
        }
    }

    async function handleDelete(reviewId) {
        setDeleteError(null);
        try {
            await api.delete(`/api/products/${productSlug}/reviews/${reviewId}`);
            setConfirmDeleteId(null);
            setEligibility((prev) => (prev ? { ...prev, can_review: true, already_reviewed: false } : prev));
            await loadReviews();
        } catch {
            setConfirmDeleteId(null);
            setDeleteError(t('reviews_delete_error'));
        }
    }

    return (
        <section className="mt-16 border-t border-line pt-10">
            <h2 className="font-serif text-2xl">{t('reviews_title')}</h2>

            <div className="mt-3 flex items-center gap-3">
                {meta.count > 0 ? (
                    <>
                        <StarRating value={meta.average_rating} />
                        <span className="text-sm text-ink-soft">
                            {t('reviews_average_summary', { rating: meta.average_rating, count: meta.count })}
                        </span>
                    </>
                ) : (
                    !loading && <p className="text-sm text-ink-soft">{t('reviews_no_reviews')}</p>
                )}
            </div>

            {deleteError && (
                <p role="alert" className="mt-4 text-sm text-red-700">
                    {deleteError}
                </p>
            )}

            {reviews.length > 0 && (
                <ul className="mt-6 space-y-6">
                    {reviews.map((review) => (
                        <li key={review.id} className="border-b border-line pb-6 last:border-0">
                            {editingReviewId === review.id ? (
                                <form
                                    onSubmit={(e) => handleEditSubmit(e, review.id)}
                                    className="max-w-md space-y-4"
                                >
                                    <StarRating value={editRating} onChange={setEditRating} size="lg" />
                                    <div>
                                        <label htmlFor={`review-edit-body-${review.id}`} className="mb-1 block text-sm">
                                            {t('reviews_body_label')}
                                        </label>
                                        <textarea
                                            id={`review-edit-body-${review.id}`}
                                            value={editBody}
                                            onChange={(e) => setEditBody(e.target.value)}
                                            rows={4}
                                            maxLength={5000}
                                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                                        />
                                    </div>
                                    {editError && (
                                        <p role="alert" className="text-sm text-red-700">
                                            {editError}
                                        </p>
                                    )}
                                    <span className="flex items-center gap-4">
                                        <button
                                            type="submit"
                                            disabled={editSubmitting}
                                            className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                                        >
                                            {t('reviews_edit_save')}
                                        </button>
                                        <button type="button" onClick={cancelEdit} className="text-sm underline">
                                            {t('reviews_edit_cancel')}
                                        </button>
                                    </span>
                                </form>
                            ) : (
                                <>
                                    <div className="flex items-center gap-3">
                                        <StarRating value={review.rating} />
                                        <span className="text-sm font-medium">{review.reviewer_name}</span>
                                    </div>
                                    {review.body && <p className="mt-2 leading-relaxed text-ink-soft">{review.body}</p>}
                                    {review.is_own && (
                                        <div className="mt-2 flex items-center gap-4 text-sm">
                                            <button type="button" onClick={() => startEdit(review)} className="underline">
                                                {t('reviews_edit_button')}
                                            </button>
                                            {confirmDeleteId === review.id ? (
                                                <span className="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(review.id)}
                                                        className="font-medium text-red-700 underline"
                                                    >
                                                        {t('reviews_confirm_delete_yes')}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setConfirmDeleteId(null)}
                                                        className="underline"
                                                    >
                                                        {t('reviews_confirm_delete_no')}
                                                    </button>
                                                </span>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => setConfirmDeleteId(review.id)}
                                                    className="text-red-700 underline hover:text-red-800"
                                                >
                                                    {t('reviews_delete_button')}
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <div className="mt-10">
                {!user && (
                    <p className="text-sm text-ink-soft">
                        <Link to="/login" className="underline">{t('reviews_login_prompt')}</Link>
                    </p>
                )}

                {user && eligibility && !eligibility.has_purchased && (
                    <p className="text-sm text-ink-soft">{t('reviews_not_purchased')}</p>
                )}

                {user && eligibility?.has_purchased && eligibility?.already_reviewed && !success && (
                    <p className="text-sm text-ink-soft">{t('reviews_already_reviewed')}</p>
                )}

                {success && <p role="status" className="text-sm text-ink-soft">{t('reviews_submit_success')}</p>}

                {user && eligibility?.can_review && !success && (
                    <form onSubmit={handleSubmit} className="max-w-md space-y-4">
                        <h3 className="text-sm font-medium">{t('reviews_write_title')}</h3>
                        <StarRating value={rating} onChange={setRating} size="lg" />
                        <div>
                            <label htmlFor="review-body" className="mb-1 block text-sm">
                                {t('reviews_body_label')}
                            </label>
                            <textarea
                                id="review-body"
                                value={body}
                                onChange={(e) => setBody(e.target.value)}
                                rows={4}
                                maxLength={5000}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        {error && (
                            <p role="alert" className="text-sm text-red-700">
                                {error}
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={submitting}
                            className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                        >
                            {t('reviews_submit_button')}
                        </button>
                    </form>
                )}
            </div>
        </section>
    );
}
