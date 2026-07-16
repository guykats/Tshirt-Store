import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';

export default function Dashboard() {
    const { t } = useTranslation();
    const [designs, setDesigns] = useState([]);
    const [orders, setOrders] = useState([]);
    const [agents, setAgents] = useState([]);
    const [events, setEvents] = useState([]);

    function loadDesigns() {
        api.get('/api/designs', { params: { status: 'pending_approval' } })
            .then((res) => setDesigns(res.data.data));
    }

    function loadOrders() {
        api.get('/api/orders', { params: { status: 'pending_approval' } })
            .then((res) => setOrders(res.data.data));
    }

    function loadAgents() {
        api.get('/api/agent-statuses').then((res) => setAgents(res.data.data));
    }

    function loadEvents() {
        api.get('/api/system-events').then((res) => setEvents(res.data.data));
    }

    useEffect(() => {
        loadDesigns();
        loadOrders();
        loadAgents();
        loadEvents();
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
        loadEvents();
    }

    async function updateAgent(id, status, currentTask) {
        await api.patch(`/api/agent-statuses/${id}`, { status, current_task: currentTask || null });
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
                            <div>
                                <p className="font-medium">{design.title}</p>
                                <p className="text-sm text-ink-soft">{design.category}</p>
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

            <section className="mb-10">
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_agents')}</h2>
                <div className="overflow-x-auto rounded border border-line">
                    <table className="w-full text-sm">
                        <thead className="bg-parchment-dim text-left">
                            <tr>
                                <th className="px-4 py-2">{t('dashboard_agent_name')}</th>
                                <th className="px-4 py-2">{t('dashboard_agent_status')}</th>
                                <th className="px-4 py-2">{t('dashboard_agent_task')}</th>
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

            <section>
                <h2 className="mb-3 font-serif text-lg">{t('dashboard_events')}</h2>
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
        </div>
    );
}

function AgentRow({ agent, onUpdate, t }) {
    const [status, setStatus] = useState(agent.status);
    const [task, setTask] = useState(agent.current_task || '');

    return (
        <tr className="border-t border-line">
            <td className="px-4 py-2 font-medium">{agent.agent_name}</td>
            <td className="px-4 py-2">
                <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="rounded border border-line px-2 py-1"
                >
                    <option value="idle">IDLE</option>
                    <option value="pending_approval">PENDING_APPROVAL</option>
                    <option value="executing">EXECUTING</option>
                </select>
            </td>
            <td className="px-4 py-2">
                <div className="flex gap-2">
                    <input
                        value={task}
                        onChange={(e) => setTask(e.target.value)}
                        placeholder={t('dashboard_agent_task')}
                        className="w-full rounded border border-line px-2 py-1"
                    />
                    <button
                        onClick={() => onUpdate(agent.id, status, task)}
                        className="shrink-0 rounded bg-ink px-3 py-1 text-white"
                    >
                        {t('save')}
                    </button>
                </div>
            </td>
        </tr>
    );
}
