import { useTranslation } from 'react-i18next';
import { formatPrice } from '../lib/formatPrice';

export const ORDER_STATUS_STYLES = {
    pending_approval: 'bg-line text-ink-soft',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-blue-100 text-blue-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-red-100 text-red-800',
};

export const ORDER_PAYMENT_STYLES = {
    unpaid: 'bg-line text-ink-soft',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
};

/**
 * Shared rendering for a single order's summary card — order number, date,
 * status/payment badges, line items, and tracking info. Used both by the
 * authenticated Orders page and the public "Track Your Order" page, so
 * anything that needs an authenticated session (invoice download, self-serve
 * cancellation) is passed in as `footer` by the caller rather than baked in
 * here, since the public lookup flow has no session to make those calls with.
 */
export default function OrderCard({ order, footer }) {
    const { t, i18n } = useTranslation();

    return (
        <div className="rounded border border-line p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="font-medium">{order.order_number}</p>
                    <p className="text-xs text-ink-soft">
                        {order.created_at && new Date(order.created_at).toLocaleDateString()}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <span className={`rounded-full px-2 py-0.5 text-xs ${ORDER_PAYMENT_STYLES[order.payment_status] ?? ''}`}>
                        {t(`orders_payment_${order.payment_status}`)}
                    </span>
                    <span className={`rounded-full px-2 py-0.5 text-xs ${ORDER_STATUS_STYLES[order.status] ?? ''}`}>
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
                {footer && <div className="flex items-center gap-3">{footer}</div>}
            </div>
        </div>
    );
}
