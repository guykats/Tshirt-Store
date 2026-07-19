import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { formatPrice } from '../lib/formatPrice';

const STATUS_STYLES = {
    pending_approval: 'bg-line text-ink-soft',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-blue-100 text-blue-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-red-100 text-red-800',
};

const PAYMENT_STYLES = {
    unpaid: 'bg-line text-ink-soft',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
};

function isCancellable(order) {
    return ['pending_approval', 'approved'].includes(order.status) && order.payment_status !== 'paid';
}

export default function Orders() {
    const { t, i18n } = useTranslation();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [confirmingId, setConfirmingId] = useState(null);
    const [cancellingId, setCancellingId] = useState(null);
    const [cancelError, setCancelError] = useState(null);

    useDocumentMeta(t('meta_orders_title', { app: t('app_name') }));

    useEffect(() => {
        api.get('/api/orders')
            .then((res) => setOrders(res.data.data))
            .finally(() => setLoading(false));
    }, []);

    function handleCancel(orderId) {
        setCancelError(null);
        setCancellingId(orderId);
        api.post(`/api/orders/${orderId}/cancel`)
            .then((res) => {
                setOrders((prev) => prev.map((order) => (order.id === orderId ? res.data.data : order)));
                setConfirmingId(null);
            })
            .catch(() => {
                setCancelError(t('orders_cancel_error'));
            })
            .finally(() => setCancellingId(null));
    }

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <h1 className="mb-6 font-serif text-2xl">{t('orders_title')}</h1>

            {loading && <p className="text-ink-soft">…</p>}
            {!loading && orders.length === 0 && <p className="text-ink-soft">{t('orders_empty')}</p>}

            <ul className="space-y-4">
                {orders.map((order) => (
                    <li key={order.id} className="rounded border border-line p-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p className="font-medium">{order.order_number}</p>
                                <p className="text-xs text-ink-soft">
                                    {order.created_at && new Date(order.created_at).toLocaleDateString()}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className={`rounded-full px-2 py-0.5 text-xs ${PAYMENT_STYLES[order.payment_status] ?? ''}`}>
                                    {t(`orders_payment_${order.payment_status}`)}
                                </span>
                                <span className={`rounded-full px-2 py-0.5 text-xs ${STATUS_STYLES[order.status] ?? ''}`}>
                                    {t(`orders_status_${order.status}`)}
                                </span>
                            </div>
                        </div>

                        <ul className="mt-3 space-y-1 text-sm text-ink-soft">
                            {order.items.map((item) => (
                                <li key={item.id}>
                                    {item.quantity} × {item.product_variant?.product?.name ?? t('orders_unknown_product')}
                                    {item.product_variant && ` (${item.product_variant.size} / ${item.product_variant.color})`}
                                </li>
                            ))}
                        </ul>

                        {order.tracking_number && (
                            <p className="mt-3 text-sm text-ink-soft">
                                {t('orders_carrier_label')}: {order.carrier} — {t('orders_tracking_number_label')}: {order.tracking_number}
                                {order.tracking_url && (
                                    <>
                                        {' '}
                                        <a
                                            href={order.tracking_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-brass hover:underline"
                                        >
                                            {t('orders_track_package')}
                                        </a>
                                    </>
                                )}
                            </p>
                        )}

                        <div className="mt-3 flex items-center justify-between border-t border-line pt-3">
                            <p className="text-sm">
                                {formatPrice(order.total_amount, order.currency, i18n.language)}
                            </p>
                            <div className="flex items-center gap-3">
                                {order.payment_status === 'paid' && (
                                    <a
                                        href={`/api/orders/${order.id}/invoice`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-sm text-brass hover:underline"
                                    >
                                        {t('orders_download_invoice')}
                                    </a>
                                )}
                                {isCancellable(order) && confirmingId !== order.id && (
                                    <button
                                        type="button"
                                        onClick={() => setConfirmingId(order.id)}
                                        className="text-sm text-red-700 hover:underline"
                                    >
                                        {t('orders_cancel_button')}
                                    </button>
                                )}
                            </div>
                        </div>

                        {confirmingId === order.id && (
                            <div className="mt-3 rounded border border-red-200 bg-red-50 p-3">
                                <p className="text-sm text-ink">{t('orders_cancel_confirm_prompt')}</p>
                                <div className="mt-2 flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => handleCancel(order.id)}
                                        disabled={cancellingId === order.id}
                                        className="rounded bg-red-700 px-3 py-1.5 text-sm text-white disabled:opacity-60"
                                    >
                                        {cancellingId === order.id ? t('orders_cancel_in_progress') : t('orders_cancel_confirm_yes')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setConfirmingId(null)}
                                        disabled={cancellingId === order.id}
                                        className="text-sm text-ink-soft hover:underline"
                                    >
                                        {t('orders_cancel_confirm_no')}
                                    </button>
                                </div>
                                {cancelError && (
                                    <p role="alert" className="mt-2 text-sm text-red-700">
                                        {cancelError}
                                    </p>
                                )}
                            </div>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
