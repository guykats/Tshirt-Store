import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate, useParams } from 'react-router-dom';
import { PayPalButtons } from '@paypal/react-paypal-js';
import api from '../lib/api';
import { useAuth } from '../lib/AuthContext';

export default function Checkout() {
    const { t } = useTranslation();
    const { productId } = useParams();
    const { user, loading: authLoading } = useAuth();
    const navigate = useNavigate();

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

    useEffect(() => {
        api.get(`/api/products/${productId}`).then((res) => {
            setProduct(res.data.data);
            const firstInStock = res.data.data.variants.find((v) => v.stock_quantity > 0);
            if (firstInStock) setVariantId(String(firstInStock.id));
        });
    }, [productId]);

    if (!authLoading && !user) {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <p className="mb-4">{t('checkout_login_required')}</p>
                <button
                    onClick={() => navigate('/login')}
                    className="rounded bg-neutral-900 px-4 py-2 text-white"
                >
                    {t('nav_login')}
                </button>
            </div>
        );
    }

    if (!product) return null;

    async function createOrder() {
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
        }
    }

    async function onApprove(data) {
        const orderId = order?.id;
        const res = await api.post(`/api/checkout/${orderId}/capture`);
        setOrder(res.data.data);
        setStatus('paid');
    }

    if (status === 'paid') {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <h1 className="mb-4 text-2xl font-semibold">{t('checkout_success')}</h1>
                <p className="text-neutral-500">{t('checkout_order_number', { number: order.order_number })}</p>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-lg px-6 py-10">
            <h1 className="mb-6 text-2xl font-semibold">{t('checkout_title')}</h1>

            <div className="mb-6 rounded border border-neutral-200 p-4">
                <p className="font-medium">{product.name}</p>
                <p className="text-sm text-neutral-500">{product.currency} {product.base_price.toFixed(2)}</p>
            </div>

            {status === 'form' && (
                <div className="space-y-4">
                    <div>
                        <label className="mb-1 block text-sm">{t('checkout_variant')}</label>
                        <select
                            value={variantId}
                            onChange={(e) => setVariantId(e.target.value)}
                            className="w-full rounded border border-neutral-300 px-3 py-2"
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
                            className="w-full rounded border border-neutral-300 px-3 py-2"
                        />
                    </div>

                    {['full_name', 'line1', 'line2', 'city', 'state', 'postal_code', 'phone'].map((field) => (
                        <div key={field}>
                            <label className="mb-1 block text-sm">{t(`address_${field}`)}</label>
                            <input
                                required={field !== 'line2' && field !== 'phone'}
                                value={address[field]}
                                onChange={(e) => setAddress((a) => ({ ...a, [field]: e.target.value }))}
                                className="w-full rounded border border-neutral-300 px-3 py-2"
                            />
                        </div>
                    ))}

                    {error && <p className="text-sm text-red-600">{error}</p>}

                    <button
                        onClick={() => createOrder().catch(() => {})}
                        className="w-full rounded bg-neutral-900 px-4 py-2 text-white"
                    >
                        {t('checkout_continue_to_payment')}
                    </button>
                </div>
            )}

            {status === 'paying' && paypalOrderId && (
                <div>
                    <p className="mb-4 text-sm text-neutral-500">{t('checkout_complete_with_paypal')}</p>
                    <PayPalButtons
                        createOrder={() => Promise.resolve(paypalOrderId)}
                        onApprove={onApprove}
                        onError={() => setError(t('checkout_error'))}
                    />
                    {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
                </div>
            )}
        </div>
    );
}
