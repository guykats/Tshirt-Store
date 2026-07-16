import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';

export default function Dashboard() {
    const { t } = useTranslation();
    const [designs, setDesigns] = useState([]);
    const [orders, setOrders] = useState([]);

    function loadDesigns() {
        api.get('/api/designs', { params: { status: 'pending_approval' } })
            .then((res) => setDesigns(res.data.data));
    }

    function loadOrders() {
        api.get('/api/orders', { params: { status: 'pending_approval' } })
            .then((res) => setOrders(res.data.data));
    }

    useEffect(() => {
        loadDesigns();
        loadOrders();
    }, []);

    async function approveDesign(id) {
        await api.post(`/api/designs/${id}/approve`);
        loadDesigns();
    }

    async function rejectDesign(id) {
        await api.post(`/api/designs/${id}/reject`);
        loadDesigns();
    }

    async function approveOrder(id) {
        await api.post(`/api/orders/${id}/approve`);
        loadOrders();
    }

    return (
        <div className="mx-auto max-w-5xl px-6 py-10">
            <h1 className="mb-6 text-2xl font-semibold">{t('dashboard_title')}</h1>

            <section className="mb-10">
                <h2 className="mb-3 text-lg font-medium">{t('dashboard_designs')}</h2>
                {designs.length === 0 && <p className="text-neutral-500">{t('no_pending_designs')}</p>}
                <ul className="space-y-3">
                    {designs.map((design) => (
                        <li key={design.id} className="flex items-center justify-between rounded border border-neutral-200 p-4">
                            <div>
                                <p className="font-medium">{design.title}</p>
                                <p className="text-sm text-neutral-500">{design.category}</p>
                            </div>
                            <div className="space-x-2">
                                <button
                                    onClick={() => approveDesign(design.id)}
                                    className="rounded bg-green-600 px-3 py-1.5 text-sm text-white"
                                >
                                    {t('approve')}
                                </button>
                                <button
                                    onClick={() => rejectDesign(design.id)}
                                    className="rounded bg-red-600 px-3 py-1.5 text-sm text-white"
                                >
                                    {t('reject')}
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            </section>

            <section>
                <h2 className="mb-3 text-lg font-medium">{t('dashboard_orders')}</h2>
                {orders.length === 0 && <p className="text-neutral-500">{t('no_pending_orders')}</p>}
                <ul className="space-y-3">
                    {orders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between rounded border border-neutral-200 p-4">
                            <div>
                                <p className="font-medium">{order.order_number}</p>
                                <p className="text-sm text-neutral-500">
                                    {order.currency} {order.total_amount.toFixed(2)}
                                </p>
                            </div>
                            <button
                                onClick={() => approveOrder(order.id)}
                                className="rounded bg-green-600 px-3 py-1.5 text-sm text-white"
                            >
                                {t('approve')}
                            </button>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
