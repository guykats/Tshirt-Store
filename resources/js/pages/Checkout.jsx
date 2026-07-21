import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { PayPalButtons } from '@paypal/react-paypal-js';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { formatPrice } from '../lib/formatPrice';
import DesignArt from '../components/DesignArt';

// CheckoutController returns a plain-English `message` for coupon
// rejections (see CouponService::validate) — most of those already read
// fine untranslated, but the per-customer-cap one is new and worth a real
// Hebrew translation, so map that one known backend string to an i18n key
// and fall back to the raw message for everything else, same as today.
const KNOWN_BACKEND_ERROR_KEYS = {
    'You have already used this coupon the maximum number of times allowed.': 'checkout_coupon_customer_limit_reached',
};

function translateCheckoutError(t, rawMessage, fallbackKey) {
    const key = rawMessage ? KNOWN_BACKEND_ERROR_KEYS[rawMessage] : null;
    if (key) return t(key);
    return rawMessage || t(fallbackKey);
}

/**
 * The post-purchase confirmation screen. Split out from Checkout's render
 * body so it has a stable, directly-testable/previewable shape — everything
 * it needs (order summary, total, tracking link) already lives on the
 * `order` object returned by CheckoutController::capture(), no extra
 * fetch required. `user` may be null (guest checkout).
 */
export function OrderConfirmation({ order, user, t, i18n }) {
    return (
        <div className="mx-auto max-w-lg px-6 py-16 text-center">
            <DesignArt
                motif="chai"
                className="mx-auto mb-6 h-28 w-28 rounded-full"
                label={t('checkout_success_art_label')}
            />
            <h1 className="mb-2 font-serif text-2xl">{t('checkout_success')}</h1>
            <p className="mb-8 text-ink-soft">{t('checkout_order_number', { number: order.order_number })}</p>

            <div className="rounded border border-line p-4 text-start">
                <h2 className="mb-3 text-sm tracking-wide text-ink-soft uppercase">
                    {t('checkout_order_summary_title')}
                </h2>
                <ul className="space-y-1 text-sm text-ink-soft">
                    {order.items.map((item) => (
                        <li key={item.id}>
                            {item.quantity} × {item.product_variant?.product?.name ?? t('orders_unknown_product')}
                            {item.product_variant && ` (${item.product_variant.size} / ${item.product_variant.color})`}
                        </li>
                    ))}
                </ul>
                <div className="mt-3 flex items-center justify-between border-t border-line pt-3">
                    <span className="text-sm">{t('checkout_order_total_label')}</span>
                    <span className="text-sm font-medium">
                        {formatPrice(order.total_amount, order.currency, i18n.language)}
                    </span>
                </div>
            </div>

            <div className="mt-8 flex flex-col items-center gap-3">
                <Link
                    to="/"
                    className="w-full rounded bg-ink px-4 py-3 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft"
                >
                    {t('checkout_continue_shopping')}
                </Link>
                {order.tracking_url && (
                    <a
                        href={order.tracking_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-sm text-brass hover:underline"
                    >
                        {t('checkout_track_order')}
                    </a>
                )}
                {!user && (
                    <p className="text-xs text-ink-soft">
                        {t('checkout_success_guest_notice')}{' '}
                        <Link to="/track-order" className="underline hover:text-ink">
                            {t('footer_track_order_link')}
                        </Link>
                    </p>
                )}
            </div>
        </div>
    );
}

export default function Checkout() {
    const { t, i18n } = useTranslation();
    const { productId } = useParams();
    const [searchParams] = useSearchParams();
    const { user, loading: authLoading } = useAuth();
    const navigate = useNavigate();

    useDocumentMeta(t('meta_checkout_title', { app: t('app_name') }));

    const [product, setProduct] = useState(null);
    const [variantId, setVariantId] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [email, setEmail] = useState('');
    const [couponCode, setCouponCode] = useState('');
    const [address, setAddress] = useState({
        full_name: '', line1: '', line2: '', city: '', state: '', postal_code: '', country: 'US', phone: '',
    });
    const [savedAddresses, setSavedAddresses] = useState([]);
    // 'new' means "enter a brand-new address below"; any other value is the
    // id (as a string, to match <select> option values) of a saved address.
    const [selectedAddressOption, setSelectedAddressOption] = useState('new');
    const [order, setOrder] = useState(null);
    const [paypalOrderId, setPaypalOrderId] = useState(null);
    const [error, setError] = useState(null);
    const [status, setStatus] = useState('form');
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        api.get(`/api/products/${productId}`).then((res) => {
            setProduct(res.data.data);
            const preselected = searchParams.get('variant');
            const match = preselected && res.data.data.variants.find((v) => String(v.id) === preselected);
            const fallback = res.data.data.variants.find((v) => v.stock_quantity > 0);
            const initial = match || fallback;
            if (initial) setVariantId(String(initial.id));
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [productId]);

    // A logged-in customer with saved addresses gets to pick from their
    // address book instead of always seeing blank inline fields; a guest
    // (no session yet) keeps the exact always-blank-fields experience.
    useEffect(() => {
        if (!user) return;
        api.get('/api/account/addresses').then((res) => {
            const list = res.data.data;
            setSavedAddresses(list);
            const defaultAddress = list.find((a) => a.is_default);
            if (defaultAddress) setSelectedAddressOption(String(defaultAddress.id));
        });
    }, [user]);

    if (authLoading || !product) return null;

    async function createOrder() {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        const usingSavedAddress = user && savedAddresses.length > 0 && selectedAddressOption !== 'new';
        try {
            const res = await api.post('/api/checkout', {
                ...(user ? {} : { email }),
                ...(couponCode.trim() ? { code: couponCode.trim() } : {}),
                product_variant_id: Number(variantId),
                quantity: Number(quantity),
                ...(usingSavedAddress
                    ? { shipping_address_id: Number(selectedAddressOption) }
                    : { shipping_address: address }),
            });
            setOrder(res.data.order);
            setPaypalOrderId(res.data.paypal_order_id);
            setStatus('paying');
            return res.data.paypal_order_id;
        } catch (err) {
            setError(translateCheckoutError(t, err.response?.data?.message, 'checkout_error'));
            throw err;
        } finally {
            setSubmitting(false);
        }
    }

    async function onApprove() {
        setError(null);
        const orderId = order?.id;
        try {
            const res = await api.post(`/api/checkout/${orderId}/capture`);
            setOrder(res.data.data);
            setStatus('paid');
        } catch (err) {
            setError(err.response?.data?.message || t('checkout_error'));
        }
    }

    if (status === 'paid') {
        return <OrderConfirmation order={order} user={user} t={t} i18n={i18n} />;
    }

    return (
        <div className="mx-auto max-w-lg px-6 py-12">
            <h1 className="mb-6 font-serif text-2xl">{t('checkout_title')}</h1>

            <div className="mb-6 rounded border border-line p-4">
                <p className="font-medium">{product.name}</p>
                <p className="text-sm text-ink-soft">{formatPrice(product.base_price, product.currency, i18n.language)}</p>
            </div>

            {status === 'form' && (
                <div className="space-y-4">
                    {!user && (
                        <p className="rounded border border-line bg-parchment-dim p-3 text-sm text-ink-soft">
                            {t('checkout_guest_notice')}{' '}
                            <button type="button" onClick={() => navigate('/login')} className="underline hover:text-ink">
                                {t('nav_login')}
                            </button>
                        </p>
                    )}
                    {!user && (
                        <div>
                            <label htmlFor="checkout-email" className="mb-1 block text-sm">{t('email')}</label>
                            <input
                                id="checkout-email"
                                type="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                    )}
                    <div>
                        <label htmlFor="checkout-variant" className="mb-1 block text-sm">{t('checkout_variant')}</label>
                        <select
                            id="checkout-variant"
                            value={variantId}
                            onChange={(e) => setVariantId(e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        >
                            {product.variants.map((v) => (
                                <option key={v.id} value={v.id} disabled={v.stock_quantity === 0}>
                                    {v.size} / {v.color} {v.stock_quantity === 0 ? `(${t('checkout_out_of_stock')})` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="checkout-quantity" className="mb-1 block text-sm">{t('checkout_quantity')}</label>
                        <input
                            id="checkout-quantity"
                            type="number"
                            min="1"
                            max="20"
                            value={quantity}
                            onChange={(e) => setQuantity(e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>

                    {user && savedAddresses.length > 0 && (
                        <div>
                            <label htmlFor="checkout-saved-address" className="mb-1 block text-sm">
                                {t('checkout_saved_address_label')}
                            </label>
                            <select
                                id="checkout-saved-address"
                                value={selectedAddressOption}
                                onChange={(e) => setSelectedAddressOption(e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            >
                                {savedAddresses.map((a) => (
                                    <option key={a.id} value={String(a.id)}>
                                        {t('checkout_saved_address_option', { name: a.full_name, line1: a.line1, city: a.city })}
                                    </option>
                                ))}
                                <option value="new">{t('checkout_saved_address_new_option')}</option>
                            </select>
                        </div>
                    )}

                    {(!user || savedAddresses.length === 0 || selectedAddressOption === 'new') &&
                        ['full_name', 'line1', 'line2', 'city', 'state', 'postal_code', 'phone'].map((field) => (
                            <div key={field}>
                                <label htmlFor={`checkout-${field}`} className="mb-1 block text-sm">{t(`address_${field}`)}</label>
                                <input
                                    id={`checkout-${field}`}
                                    required={field !== 'line2' && field !== 'phone'}
                                    value={address[field]}
                                    onChange={(e) => setAddress((a) => ({ ...a, [field]: e.target.value }))}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2"
                                />
                            </div>
                        ))}

                    <div>
                        <label htmlFor="checkout-coupon" className="mb-1 block text-sm">{t('checkout_coupon_label')}</label>
                        <input
                            id="checkout-coupon"
                            type="text"
                            value={couponCode}
                            onChange={(e) => setCouponCode(e.target.value)}
                            placeholder={t('checkout_coupon_placeholder')}
                            className="w-full rounded border border-line bg-parchment px-3 py-2 uppercase"
                        />
                    </div>

                    {error && <p role="alert" className="text-sm text-red-700">{error}</p>}

                    <button
                        onClick={() => createOrder().catch(() => {})}
                        disabled={submitting}
                        className="w-full rounded bg-ink px-4 py-3 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                    >
                        {t('checkout_continue_to_payment')}
                    </button>
                </div>
            )}

            {status === 'paying' && paypalOrderId && (
                <div>
                    {order?.discount_amount > 0 && (
                        <p className="mb-4 rounded border border-line bg-parchment-dim p-3 text-sm text-ink-soft">
                            {t('checkout_coupon_applied', {
                                code: order.discount_code,
                                amount: formatPrice(order.discount_amount, order.currency, i18n.language),
                            })}
                        </p>
                    )}
                    <p className="mb-4 text-sm text-ink-soft">{t('checkout_complete_with_paypal')}</p>
                    <PayPalButtons
                        createOrder={() => Promise.resolve(paypalOrderId)}
                        onApprove={onApprove}
                        onError={() => setError(t('checkout_error'))}
                    />
                    {error && <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>}
                </div>
            )}
        </div>
    );
}
