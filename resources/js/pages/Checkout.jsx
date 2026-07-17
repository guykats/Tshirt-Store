import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { PayPalButtons } from '@paypal/react-paypal-js';
import api from '../lib/api';
import { useAuth } from '../lib/AuthContext';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Checkout() {
    const { t } = useTranslation();
    const { productId } = useParams();
    const [searchParams] = useSearchParams();
    const { user, loading: authLoading } = useAuth();
    const navigate = useNavigate();

    useDocumentMeta(t('meta_checkout_title', { app: t('app_name') }));

    const [product, setProduct] = useState(null);
    const [variantId, setVariantId] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [address, setAddress] = useState({
        full_name: '', line1: '', line2: '', city: '', state: '', postal_code: '', country: 'US', phone: '',
    });
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

    if (!authLoading && !user) {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <p className="mb-4 text-ink-soft">{t('checkout_login_required')}</p>
                <button onClick={() => navigate('/login')} className="rounded bg-ink px-5 py-2.5 text-sm text-parchment">
                    {t('nav_login')}
                </button>
            </div>
        );
    }

    if (!product) return null;

    async function createOrder() {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const res = await api.post('/api/checkout', {
                product_variant_id: Number(variantId),
                quantity: Number(quantity),
                shipping_address: address,
            });
            setOrder(res.data.order);
            setPaypalOrderId(res.data.paypal_order_id);
            setStatus('paying');
            return res.data.paypal_order_id;
        } catch (err) {
            setError(err.response?.data?.message || t('checkout_error'));
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
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <h1 className="mb-4 font-serif text-2xl">{t('checkout_success')}</h1>
                <p className="text-ink-soft">{t('checkout_order_number', { number: order.order_number })}</p>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-lg px-6 py-12">
            <h1 className="mb-6 font-serif text-2xl">{t('checkout_title')}</h1>

            <div className="mb-6 rounded border border-line p-4">
                <p className="font-medium">{product.name}</p>
                <p className="text-sm text-ink-soft">{product.currency} {product.base_price.toFixed(2)}</p>
            </div>

            {status === 'form' && (
                <div className="space-y-4">
                    <div>
                        <label className="mb-1 block text-sm">{t('checkout_variant')}</label>
                        <select
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
                        <label className="mb-1 block text-sm">{t('checkout_quantity')}</label>
                        <input
                            type="number"
                            min="1"
                            max="20"
                            value={quantity}
                            onChange={(e) => setQuantity(e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>

                    {['full_name', 'line1', 'line2', 'city', 'state', 'postal_code', 'phone'].map((field) => (
                        <div key={field}>
                            <label className="mb-1 block text-sm">{t(`address_${field}`)}</label>
                            <input
                                required={field !== 'line2' && field !== 'phone'}
                                value={address[field]}
                                onChange={(e) => setAddress((a) => ({ ...a, [field]: e.target.value }))}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                    ))}

                    {error && <p className="text-sm text-red-700">{error}</p>}

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
                    <p className="mb-4 text-sm text-ink-soft">{t('checkout_complete_with_paypal')}</p>
                    <PayPalButtons
                        createOrder={() => Promise.resolve(paypalOrderId)}
                        onApprove={onApprove}
                        onError={() => setError(t('checkout_error'))}
                    />
                    {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
                </div>
            )}
        </div>
    );
}
