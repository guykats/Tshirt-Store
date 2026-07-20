import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import OrderCard from '../components/OrderCard';
import useDocumentMeta from '../hooks/useDocumentMeta';

/**
 * Public, no-auth order lookup for a guest whose checkout session has
 * ended — see POST /api/orders/lookup (OrderController::lookup). Reuses
 * OrderCard, the same order-summary rendering the authenticated Orders page
 * uses, but without the invoice-download/cancel footer actions since those
 * require a live session this page deliberately doesn't have.
 */
export default function TrackOrder() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_track_order_title', { app: t('app_name') }));

    const [orderNumber, setOrderNumber] = useState('');
    const [email, setEmail] = useState('');
    const [order, setOrder] = useState(null);
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setError(null);
        setOrder(null);
        setSubmitting(true);
        try {
            const res = await api.post('/api/orders/lookup', {
                order_number: orderNumber.trim(),
                email: email.trim(),
            });
            setOrder(res.data.data);
        } catch {
            setError(t('track_order_error'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto max-w-lg px-6 py-16">
            <h1 className="mb-2 font-serif text-2xl">{t('track_order_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('track_order_intro')}</p>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="track-order-number" className="mb-1 block text-sm">
                        {t('track_order_order_number_label')}
                    </label>
                    <input
                        id="track-order-number"
                        type="text"
                        required
                        value={orderNumber}
                        onChange={(e) => setOrderNumber(e.target.value)}
                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                    />
                </div>
                <div>
                    <label htmlFor="track-order-email" className="mb-1 block text-sm">
                        {t('email')}
                    </label>
                    <input
                        id="track-order-email"
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
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
                    className="w-full rounded bg-ink px-4 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {submitting ? t('track_order_looking_up') : t('track_order_button')}
                </button>
            </form>

            {order && (
                <div className="mt-8">
                    <OrderCard order={order} />
                </div>
            )}
        </div>
    );
}
