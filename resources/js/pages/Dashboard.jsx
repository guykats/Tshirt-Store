import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { formatPrice } from '../lib/formatPrice';

const NEXT_FULFILLMENT_STATUS = {
    approved: 'processing',
    processing: 'shipped',
    shipped: 'delivered',
};

const CARRIER_OPTIONS = ['USPS', 'UPS', 'FedEx', 'Israel Post', 'Other'];

const DEFAULT_SHIPPING_DRAFT = { carrier: CARRIER_OPTIONS[0], otherCarrier: '', trackingNumber: '' };

/**
 * Both /api/orders and /api/designs paginate 20-at-a-time server-side. The
 * dashboard's admin queues (fulfillment, refunds, pending approvals) need the
 * *entire* matching set to filter over client-side, not just page 1 — a store
 * with more than 20 total orders or more than 20 pending designs would
 * otherwise silently drop older rows with no error or "load more" affordance.
 * Loop through meta.last_page (same pattern AuditLog.jsx uses for its visible
 * pager) until every page has been fetched.
 */
async function fetchAllPages(url, params = {}) {
    const results = [];
    let page = 1;
    let lastPage = 1;

    do {
        const res = await api.get(url, { params: { ...params, page } });
        results.push(...res.data.data);
        lastPage = res.data.meta?.last_page ?? 1;
        page += 1;
    } while (page <= lastPage);

    return results;
}

export default function Dashboard() {
    const { t, i18n } = useTranslation();

    useDocumentMeta(t('meta_dashboard_title', { app: t('app_name') }));

    const [designs, setDesigns] = useState([]);
    const [orders, setOrders] = useState([]);
    const [fulfillmentOrders, setFulfillmentOrders] = useState([]);
    const [refundableOrders, setRefundableOrders] = useState([]);
    const [advancingOrderId, setAdvancingOrderId] = useState(null);
    const [shippingDrafts, setShippingDrafts] = useState({});
    const [shippingErrors, setShippingErrors] = useState({});
    const [refundingOrderId, setRefundingOrderId] = useState(null);
    const [agents, setAgents] = useState([]);
    const [events, setEvents] = useState([]);
    const [activity, setActivity] = useState([]);
    const [taskCounts, setTaskCounts] = useState({ todo: 0, in_progress: 0, blocked: 0, done: 0 });
    const [lowStock, setLowStock] = useState([]);

    function loadDesigns() {
        fetchAllPages('/api/designs', { status: 'pending_approval' }).then(setDesigns);
    }

    function loadOrders() {
        fetchAllPages('/api/orders', { status: 'pending_approval' }).then(setOrders);
    }

    function loadFulfillmentOrders() {
        fetchAllPages('/api/orders').then((allOrders) => {
            setFulfillmentOrders(allOrders.filter((order) => order.status in NEXT_FULFILLMENT_STATUS));
            setRefundableOrders(allOrders.filter((order) => order.payment_status === 'paid'));
        });
    }

    function loadAgents() {
        api.get('/api/agent-statuses').then((res) => setAgents(res.data.data));
    }

    function loadEvents() {
        api.get('/api/system-events').then((res) => setEvents(res.data.data));
    }

    function loadActivity() {
        api.get('/api/activity').then((res) => setActivity(res.data.data));
    }

    function loadTaskCounts() {
        api.get('/api/project-tasks').then((res) => setTaskCounts(res.data.counts));
    }

    function loadLowStock() {
        api.get('/api/inventory/low-stock').then((res) => setLowStock(res.data.data));
    }

    useEffect(() => {
        loadDesigns();
        loadOrders();
        loadFulfillmentOrders();
        loadAgents();
        loadEvents();
        loadActivity();
        loadTaskCounts();
        loadLowStock();
    }, []);

    async function approveDesign(id) {
        await api.post(`/api/designs/${id}/approve`);
        loadDesigns();
        loadEvents();
    }

    async function rejectDesign(id) {
        await api.post(`/api/designs/${id}/reject`);
        loadDesigns();
        loadEvents();
    }

    async function approveOrder(id) {
        await api.post(`/api/orders/${id}/approve`);
        loadOrders();
        loadFulfillmentOrders();
        loadEvents();
    }

    function updateShippingDraft(orderId, field, value) {
        setShippingDrafts((prev) => ({
            ...prev,
            [orderId]: { ...(prev[orderId] ?? DEFAULT_SHIPPING_DRAFT), [field]: value },
        }));
    }

    async function advanceOrderStatus(id, nextStatus) {
        setAdvancingOrderId(id);
        setShippingErrors((prev) => ({ ...prev, [id]: null }));
        try {
            const payload = {};

            if (nextStatus === 'shipped') {
                const draft = shippingDrafts[id] ?? DEFAULT_SHIPPING_DRAFT;
                payload.carrier = (draft.carrier === 'Other' ? draft.otherCarrier : draft.carrier)?.trim();
                payload.tracking_number = draft.trackingNumber?.trim();
            }

            await api.post(`/api/orders/${id}/advance-status`, payload);
            loadFulfillmentOrders();
            loadEvents();
        } catch (err) {
            setShippingErrors((prev) => ({ ...prev, [id]: t('dashboard_fulfillment_shipping_error') }));
        } finally {
            setAdvancingOrderId(null);
        }
    }

    async function refundOrder(id) {
        setRefundingOrderId(id);
        try {
            await api.post(`/api/orders/${id}/refund`);
            loadFulfillmentOrders();
            loadOrders();
            loadEvents();
        } finally {
            setRefundingOrderId(null);
        }
    }

    async function updateAgent(id, status) {
        await api.patch(`/api/agent-statuses/${id}`, { status });
        loadAgents();
        loadEvents();
    }

    return (
        <div className="mx-auto max-w-5xl px-6 py-10">
            <h1 className="mb-6 font-serif text-2xl">{t('dashboard_title')}</h1>

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_designs')}</h2>
                {designs.length === 0 && <p className="text-ink-soft">{t('no_pending_designs')}</p>}
                <ul className="space-y-3">
                    {designs.map((design) => (
                        <li key={design.id} className="flex items-center justify-between rounded border border-line p-4">
                            <div className="flex items-center gap-4">
                                <DesignArt motif={design.mockup_url} className="h-16 w-16 shrink-0 rounded" />
                                <div>
                                    <p className="font-medium">{design.title}</p>
                                    <p className="text-sm text-ink-soft">{design.category}</p>
                                </div>
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

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_orders')}</h2>
                {orders.length === 0 && <p className="text-ink-soft">{t('no_pending_orders')}</p>}
                <ul className="space-y-3">
                    {orders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between rounded border border-line p-4">
                            <div>
                                <p className="font-medium">{order.order_number}</p>
                                <p className="text-sm text-ink-soft">
                                    {formatPrice(order.total_amount, order.currency, i18n.language)}
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

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_fulfillment')}</h2>
                <p className="mb-3 text-sm text-ink-soft">{t('dashboard_fulfillment_hint')}</p>
                {fulfillmentOrders.length === 0 && <p className="text-ink-soft">{t('dashboard_no_fulfillment_orders')}</p>}
                <ul className="space-y-3">
                    {fulfillmentOrders.map((order) => {
                        const nextStatus = NEXT_FULFILLMENT_STATUS[order.status];
                        const requiresShippingDetails = nextStatus === 'shipped';
                        const draft = shippingDrafts[order.id] ?? DEFAULT_SHIPPING_DRAFT;

                        return (
                            <li key={order.id} className="rounded border border-line p-4">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="font-medium">{order.order_number}</p>
                                        <p className="text-sm text-ink-soft">{t(`orders_status_${order.status}`)}</p>
                                    </div>
                                    {!requiresShippingDetails && (
                                        <button
                                            type="button"
                                            onClick={() => advanceOrderStatus(order.id, nextStatus)}
                                            disabled={advancingOrderId === order.id}
                                            className="rounded bg-ink px-3 py-1.5 text-sm text-white disabled:opacity-60"
                                        >
                                            {advancingOrderId === order.id
                                                ? t('dashboard_fulfillment_advancing')
                                                : t(`dashboard_fulfillment_mark_${nextStatus}`)}
                                        </button>
                                    )}
                                </div>

                                {requiresShippingDetails && (
                                    <div className="mt-3 flex flex-wrap items-end gap-3 border-t border-line pt-3">
                                        <div>
                                            <label htmlFor={`carrier-${order.id}`} className="block text-xs text-ink-soft">
                                                {t('dashboard_fulfillment_carrier_label')}
                                            </label>
                                            <select
                                                id={`carrier-${order.id}`}
                                                value={draft.carrier}
                                                onChange={(e) => updateShippingDraft(order.id, 'carrier', e.target.value)}
                                                className="rounded border border-line px-2 py-1 text-sm"
                                            >
                                                {CARRIER_OPTIONS.map((option) => (
                                                    <option key={option} value={option}>{option}</option>
                                                ))}
                                            </select>
                                        </div>
                                        {draft.carrier === 'Other' && (
                                            <div>
                                                <label htmlFor={`carrier-other-${order.id}`} className="block text-xs text-ink-soft">
                                                    {t('dashboard_fulfillment_carrier_other_label')}
                                                </label>
                                                <input
                                                    id={`carrier-other-${order.id}`}
                                                    type="text"
                                                    value={draft.otherCarrier}
                                                    onChange={(e) => updateShippingDraft(order.id, 'otherCarrier', e.target.value)}
                                                    className="rounded border border-line px-2 py-1 text-sm"
                                                />
                                            </div>
                                        )}
                                        <div>
                                            <label htmlFor={`tracking-${order.id}`} className="block text-xs text-ink-soft">
                                                {t('dashboard_fulfillment_tracking_number_label')}
                                            </label>
                                            <input
                                                id={`tracking-${order.id}`}
                                                type="text"
                                                value={draft.trackingNumber}
                                                onChange={(e) => updateShippingDraft(order.id, 'trackingNumber', e.target.value)}
                                                className="rounded border border-line px-2 py-1 text-sm"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => advanceOrderStatus(order.id, nextStatus)}
                                            disabled={advancingOrderId === order.id}
                                            className="rounded bg-ink px-3 py-1.5 text-sm text-white disabled:opacity-60"
                                        >
                                            {advancingOrderId === order.id
                                                ? t('dashboard_fulfillment_advancing')
                                                : t('dashboard_fulfillment_mark_shipped')}
                                        </button>
                                        {shippingErrors[order.id] && (
                                            <p role="alert" className="w-full text-sm text-red-700">
                                                {shippingErrors[order.id]}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </section>

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_refunds')}</h2>
                <p className="mb-3 text-sm text-ink-soft">{t('dashboard_refunds_hint')}</p>
                {refundableOrders.length === 0 && <p className="text-ink-soft">{t('dashboard_no_refundable_orders')}</p>}
                <ul className="space-y-3">
                    {refundableOrders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between rounded border border-line p-4">
                            <div>
                                <p className="font-medium">{order.order_number}</p>
                                <p className="text-sm text-ink-soft">
                                    {formatPrice(order.total_amount, order.currency, i18n.language)} — {t(`orders_status_${order.status}`)}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => refundOrder(order.id)}
                                disabled={refundingOrderId === order.id}
                                className="rounded bg-red-700 px-3 py-1.5 text-sm text-white disabled:opacity-60"
                            >
                                {refundingOrderId === order.id ? t('dashboard_refund_processing') : t('dashboard_refund_action')}
                            </button>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_low_stock')}</h2>
                <p className="mb-3 text-sm text-ink-soft">{t('dashboard_low_stock_hint')}</p>
                {lowStock.length === 0 && <p className="text-ink-soft">{t('dashboard_no_low_stock')}</p>}
                {lowStock.length > 0 && (
                    <div className="overflow-x-auto rounded border border-line">
                        <table className="w-full text-sm">
                            <thead className="bg-parchment-dim text-left">
                                <tr>
                                    <th className="px-4 py-2">{t('dashboard_low_stock_product')}</th>
                                    <th className="px-4 py-2">{t('dashboard_low_stock_variant')}</th>
                                    <th className="px-4 py-2">{t('dashboard_low_stock_remaining')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {lowStock.map((variant) => (
                                    <tr key={variant.id} className="border-t border-line">
                                        <td className="px-4 py-2 font-medium">{variant.product?.name}</td>
                                        <td className="px-4 py-2 text-ink-soft">{variant.size} / {variant.color}</td>
                                        <td className="px-4 py-2">
                                            <span className={variant.stock_quantity === 0 ? 'font-medium text-red-700' : 'font-medium text-amber-700'}>
                                                {variant.stock_quantity}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            <section className="mb-10">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="font-serif text-lg">{t('dashboard_progress')}</h2>
                    <Link to="/dashboard/progress" className="text-sm text-brass hover:underline">
                        {t('dashboard_progress_view_all')}
                    </Link>
                </div>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <ProgressStat label={t('progress_status_blocked')} value={taskCounts.blocked} tone="text-red-700" />
                    <ProgressStat label={t('progress_status_in_progress')} value={taskCounts.in_progress} tone="text-blue-700" />
                    <ProgressStat label={t('progress_status_todo')} value={taskCounts.todo} tone="text-ink-soft" />
                    <ProgressStat label={t('progress_status_done')} value={taskCounts.done} tone="text-green-700" />
                </div>
            </section>

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_agents')}</h2>
                <p className="mb-3 text-sm text-ink-soft">{t('dashboard_agents_hint')}</p>
                <div className="overflow-x-auto rounded border border-line">
                    <table className="w-full text-sm">
                        <thead className="bg-parchment-dim text-left">
                            <tr>
                                <th className="px-4 py-2">{t('dashboard_agent_name')}</th>
                                <th className="px-4 py-2">{t('dashboard_agent_status')}</th>
                                <th className="px-4 py-2">{t('dashboard_agent_task')}</th>
                                <th className="px-4 py-2">{t('dashboard_agent_backlog')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {agents.map((agent) => (
                                <AgentRow key={agent.id} agent={agent} onUpdate={updateAgent} t={t} />
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="mb-10">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="font-serif text-lg">{t('dashboard_events')}</h2>
                    <Link to="/dashboard/audit-log" className="text-sm text-brass hover:underline">
                        {t('dashboard_events_view_all')}
                    </Link>
                </div>
                <ul className="max-h-96 space-y-2 overflow-y-auto rounded border border-line p-4">
                    {events.length === 0 && <p className="text-ink-soft">{t('dashboard_no_events')}</p>}
                    {events.map((event) => (
                        <li key={event.id} className="border-b border-line pb-2 text-sm last:border-0">
                            <span className="text-ink-soft">{new Date(event.created_at).toLocaleString()}</span>
                            {' — '}
                            {event.description}
                        </li>
                    ))}
                </ul>
            </section>

            <section>
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_activity')}</h2>
                <p className="mb-3 text-sm text-ink-soft">{t('dashboard_activity_hint')}</p>
                <ul className="max-h-96 space-y-2 overflow-y-auto rounded border border-line p-4 font-mono text-sm">
                    {activity.length === 0 && <p className="text-ink-soft">{t('dashboard_no_activity')}</p>}
                    {activity.map((commit) => (
                        <li key={commit.hash} className="border-b border-line pb-2 last:border-0">
                            <span className="text-brass">{commit.hash}</span>
                            {' '}
                            <span className="text-ink-soft">{commit.author}, {commit.date && new Date(commit.date).toLocaleDateString()}</span>
                            {' — '}
                            {commit.message}
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}

function ProgressStat({ label, value, tone }) {
    return (
        <div className="rounded border border-line p-3">
            <p className={`text-2xl font-serif ${tone}`}>{value ?? 0}</p>
            <p className="mt-1 text-xs text-ink-soft">{label}</p>
        </div>
    );
}

function AgentRow({ agent, onUpdate, t }) {
    const [status, setStatus] = useState(agent.status);

    return (
        <tr className="border-t border-line">
            <td className="px-4 py-2 font-medium">{agent.agent_name}</td>
            <td className="px-4 py-2">
                <div className="flex gap-2">
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded border border-line px-2 py-1"
                    >
                        <option value="idle">IDLE</option>
                        <option value="pending_approval">PENDING_APPROVAL</option>
                        <option value="executing">EXECUTING</option>
                    </select>
                    <button
                        onClick={() => onUpdate(agent.id, status)}
                        className="shrink-0 rounded bg-ink px-3 py-1 text-white"
                    >
                        {t('save')}
                    </button>
                </div>
            </td>
            <td className="px-4 py-2">
                {agent.current_task || <span className="text-ink-soft">—</span>}
                {agent.current_task_status === 'in_progress' && (
                    <span className="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800">
                        {t('progress_status_in_progress')}
                    </span>
                )}
            </td>
            <td className="px-4 py-2 text-ink-soft">{agent.backlog_count ?? 0}</td>
        </tr>
    );
}
