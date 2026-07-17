import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const STATUSES = ['blocked', 'in_progress', 'todo', 'done'];
const STATUS_STYLES = {
    todo: 'bg-line text-ink-soft',
    in_progress: 'bg-blue-100 text-blue-800',
    blocked: 'bg-red-100 text-red-800',
    done: 'bg-green-100 text-green-800',
};

export default function ProjectProgress() {
    const { t } = useTranslation();
    const [tasks, setTasks] = useState([]);
    const [counts, setCounts] = useState({ todo: 0, in_progress: 0, blocked: 0, done: 0 });
    const [statusFilter, setStatusFilter] = useState('');
    const [agentFilter, setAgentFilter] = useState('');
    const [lightbox, setLightbox] = useState(null);

    useDocumentMeta(t('meta_progress_title', { app: t('app_name') }));

    function load() {
        api.get('/api/project-tasks', { params: { status: statusFilter || undefined, agent: agentFilter || undefined } })
            .then((res) => {
                setTasks(res.data.data);
                setCounts(res.data.counts);
            });
    }

    useEffect(load, [statusFilter, agentFilter]);

    const agents = [...new Set(tasks.map((t) => t.agent_name))].sort();

    return (
        <div className="mx-auto max-w-6xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('progress_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('progress_hint')}</p>

            <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {STATUSES.map((s) => (
                    <button
                        key={s}
                        onClick={() => setStatusFilter(statusFilter === s ? '' : s)}
                        className={`rounded border p-3 text-left ${statusFilter === s ? 'border-ink' : 'border-line'}`}
                    >
                        <p className="text-2xl font-serif">{counts[s] ?? 0}</p>
                        <p className={`mt-1 inline-block rounded-full px-2 py-0.5 text-xs ${STATUS_STYLES[s]}`}>
                            {t(`progress_status_${s}`)}
                        </p>
                    </button>
                ))}
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <select
                    value={agentFilter}
                    onChange={(e) => setAgentFilter(e.target.value)}
                    className="rounded border border-line bg-parchment px-3 py-1.5 text-sm"
                >
                    <option value="">{t('progress_all_agents')}</option>
                    {agents.map((a) => (
                        <option key={a} value={a}>{a}</option>
                    ))}
                </select>
                {(statusFilter || agentFilter) && (
                    <button
                        onClick={() => { setStatusFilter(''); setAgentFilter(''); }}
                        className="text-sm text-ink-soft underline hover:text-ink"
                    >
                        {t('progress_clear_filters')}
                    </button>
                )}
            </div>

            <div className="overflow-x-auto rounded border border-line">
                <table className="w-full text-sm">
                    <thead className="bg-parchment-dim text-left">
                        <tr>
                            <th className="px-4 py-2">{t('progress_col_task')}</th>
                            <th className="px-4 py-2">{t('progress_col_agent')}</th>
                            <th className="px-4 py-2">{t('progress_col_status')}</th>
                            <th className="px-4 py-2">{t('progress_col_evidence')}</th>
                            <th className="px-4 py-2">{t('progress_col_updated')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tasks.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-6 text-center text-ink-soft">
                                    {t('progress_no_tasks')}
                                </td>
                            </tr>
                        )}
                        {tasks.map((task) => (
                            <tr key={task.id} className="border-t border-line align-top">
                                <td className="max-w-sm px-4 py-3">
                                    <p className="font-medium">{task.title}</p>
                                    {task.description && <p className="mt-1 text-xs text-ink-soft">{task.description}</p>}
                                    {task.status === 'blocked' && task.blocked_reason && (
                                        <p className="mt-1 text-xs text-red-700">{task.blocked_reason}</p>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">{task.agent_name}</td>
                                <td className="px-4 py-3">
                                    <span className={`inline-block rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${STATUS_STYLES[task.status]}`}>
                                        {t(`progress_status_${task.status}`)}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        {task.screenshot_url && (
                                            <button onClick={() => setLightbox(task.screenshot_url)}>
                                                <img
                                                    src={task.screenshot_url}
                                                    alt={task.title}
                                                    className="h-12 w-12 rounded border border-line object-cover object-top"
                                                />
                                            </button>
                                        )}
                                        {task.commit_sha && (
                                            <a
                                                href={`https://github.com/guykats/Tshirt-Store/commit/${task.commit_sha}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="font-mono text-xs text-brass hover:underline"
                                            >
                                                {task.commit_sha.slice(0, 7)}
                                            </a>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-xs whitespace-nowrap text-ink-soft">
                                    {new Date(task.updated_at).toLocaleDateString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {lightbox && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6"
                    onClick={() => setLightbox(null)}
                >
                    <img src={lightbox} alt="" className="max-h-full max-w-full rounded shadow-lg" />
                </div>
            )}
        </div>
    );
}
