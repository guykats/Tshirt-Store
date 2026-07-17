import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const STATUS_STYLES = {
    pending_approval: 'bg-line text-ink-soft',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};

const PAYMENT_STYLES = {
    unpaid: 'bg-line text-ink-soft',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
};

export default function Orders() {
    const { t } = useTranslation();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);

    useDocumentMeta(t('meta_orders_title', { app: t('app_name') }));

    useEffect(() => {
        api.get('/api/orders')
            .then((res) => setOrders(res.data.data))
            .finally(() => setLoading(false));
    }, []);

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

                        <div className="mt-3 flex items-center justify-between border-t border-line pt-3">
                            <p className="text-sm">
                                {order.currency} {order.total_amount.toFixed(2)}
                            </p>
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
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}
