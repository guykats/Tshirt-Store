import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Dashboard() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_dashboard_title', { app: t('app_name') }));

    const [designs, setDesigns] = useState([]);
    const [orders, setOrders] = useState([]);
    const [agents, setAgents] = useState([]);
    const [events, setEvents] = useState([]);
    const [activity, setActivity] = useState([]);
    const [taskCounts, setTaskCounts] = useState({ todo: 0, in_progress: 0, blocked: 0, done: 0 });

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

    function loadActivity() {
        api.get('/api/activity').then((res) => setActivity(res.data.data));
    }

    function loadTaskCounts() {
        api.get('/api/project-tasks').then((res) => setTaskCounts(res.data.counts));
    }

    useEffect(() => {
        loadDesigns();
        loadOrders();
        loadAgents();
        loadEvents();
        loadActivity();
        loadTaskCounts();
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
