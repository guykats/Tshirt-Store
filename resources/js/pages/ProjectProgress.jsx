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
const EPIC_STATUS_STYLES = {
    proposed: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};

export default function ProjectProgress() {
    const { t } = useTranslation();
    const [tasks, setTasks] = useState([]);
    const [counts, setCounts] = useState({ todo: 0, in_progress: 0, blocked: 0, done: 0 });
    const [statusFilter, setStatusFilter] = useState('');
    const [agentFilter, setAgentFilter] = useState('');
    const [lightbox, setLightbox] = useState(null);
    const [epics, setEpics] = useState([]);
    const [showDecidedEpics, setShowDecidedEpics] = useState(false);

    useDocumentMeta(t('meta_progress_title', { app: t('app_name') }));

    function load() {
        api.get('/api/project-tasks', { params: { status: statusFilter || undefined, agent: agentFilter || undefined } })
            .then((res) => {
                setTasks(res.data.data);
                setCounts(res.data.counts);
            });
    }

    function loadEpics() {
        api.get('/api/epics').then((res) => setEpics(res.data.data));
    }

    useEffect(load, [statusFilter, agentFilter]);
    useEffect(loadEpics, []);

    async function decideEpic(epicId, action) {
        await api.post(`/api/epics/${epicId}/${action}`);
        loadEpics();
    }

    const agents = [...new Set(tasks.map((t) => t.agent_name))].sort();
    const visibleEpics = epics.filter((e) => showDecidedEpics || e.status === 'proposed');

    return (
        <div className="mx-auto max-w-6xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('progress_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('progress_hint')}</p>

            <section className="mb-12">
                <div className="mb-1 flex items-center justify-between">
                    <h2 className="font-serif text-lg">{t('epics_title')}</h2>
                    <button
                        onClick={() => setShowDecidedEpics((v) => !v)}
                        className="text-sm text-brass hover:underline"
                    >
                        {showDecidedEpics ? t('epics_hide_decided') : t('epics_show_decided')}
                    </button>
                </div>
                <p className="mb-4 text-sm text-ink-soft">{t('epics_hint')}</p>

                {visibleEpics.length === 0 && (
                    <p className="rounded border border-line p-4 text-sm text-ink-soft">{t('epics_empty')}</p>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {visibleEpics.map((epic) => (
                        <div key={epic.id} className="flex flex-col rounded border border-line p-4">
                            <div className="mb-2 flex items-start justify-between gap-2">
                                <p className="font-serif text-base">{epic.title}</p>
                                <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${EPIC_STATUS_STYLES[epic.status]}`}>
                                    {t(`epics_status_${epic.status}`)}
                                </span>
                            </div>
                            <p className="mb-3 flex-1 text-sm text-ink-soft">{epic.description}</p>
                            <div className="mb-3 flex items-center justify-between text-xs text-ink-soft">
                                <span>{epic.agent_name}</span>
                                <span>
                                    {epic.task_count === 1
                                        ? t('epics_task_count', { count: epic.task_count })
                                        : t('epics_task_count_plural', { count: epic.task_count ?? 0 })}
                                </span>
                            </div>
                            {epic.status === 'proposed' ? (
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => decideEpic(epic.id, 'approve')}
                                        className="flex-1 rounded bg-green-600 px-3 py-1.5 text-sm text-white"
                                    >
                                        {t('epics_choose')}
                                    </button>
                                    <button
                                        onClick={() => decideEpic(epic.id, 'reject')}
                                        className="flex-1 rounded bg-red-600 px-3 py-1.5 text-sm text-white"
                                    >
                                        {t('epics_reject')}
                                    </button>
                                    <button
                                        onClick={() => decideEpic(epic.id, 'delay')}
                                        className="flex-1 rounded border border-line px-3 py-1.5 text-sm"
                                    >
                                        {t('epics_delay')}
                                    </button>
                                </div>
                            ) : (
                                epic.decided_by && (
                                    <p className="text-xs text-ink-soft">{t('epics_decided_by', { name: epic.decided_by })}</p>
                                )
                            )}
                        </div>
                    ))}
                </div>
            </section>

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
