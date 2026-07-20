import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import EmptyState from '../components/EmptyState';
import OrderCard from '../components/OrderCard';
import useDocumentMeta from '../hooks/useDocumentMeta';

function isCancellable(order) {
    return ['pending_approval', 'approved'].includes(order.status) && order.payment_status !== 'paid';
}

export default function Orders() {
    const { t } = useTranslation();
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
            {!loading && orders.length === 0 && (
                <EmptyState
                    motif="pomegranate"
                    motifLabel={t('orders_empty_art_label')}
                    title={t('orders_empty_title')}
                    body={t('orders_empty')}
                />
            )}

            <ul className="space-y-4">
                {orders.map((order) => (
                    <li key={order.id}>
                        <OrderCard
                            order={order}
                            footer={
                                <>
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
                                </>
                            }
                        />

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
