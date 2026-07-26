import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const STATUSES = ['blocked', 'in_progress', 'todo', 'done'];
// Dark-mode-appropriate tints of the same semantic palette the rest of the
// app uses (bg-*-100/text-*-800 on parchment) — desaturated-on-dark tokens
// per the research cited in the task: a colored tint over a near-black
// surface reads clearly without competing with the single indigo accent.
const STATUS_STYLES = {
    todo: 'bg-white/[0.07] text-[var(--tm-text-muted)]',
    in_progress: 'bg-blue-900/40 text-blue-300',
    blocked: 'bg-red-900/40 text-red-300',
    done: 'bg-green-900/40 text-green-300',
};

// Explicit keyboard-focus ring for this dark theme — see TeamManagementNav
// for the same rationale (the UA default outline reads poorly on near-black).
const FOCUS_RING =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--tm-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--tm-surface-1)]';

export default function ProjectProgress() {
    const { t } = useTranslation();
    const [tasks, setTasks] = useState([]);
    const [counts, setCounts] = useState({ todo: 0, in_progress: 0, blocked: 0, done: 0 });
    const [statusFilter, setStatusFilter] = useState('');
    const [agentFilter, setAgentFilter] = useState('');
    const [lightbox, setLightbox] = useState(null);
    const [automation, setAutomation] = useState(null);
    const [automationBusy, setAutomationBusy] = useState(false);
    const [automationError, setAutomationError] = useState('');

    useDocumentMeta(t('meta_progress_title', { app: t('app_name') }));

    function load() {
        api.get('/api/project-tasks', { params: { status: statusFilter || undefined, agent: agentFilter || undefined } })
            .then((res) => {
                setTasks(res.data.data);
                setCounts(res.data.counts);
            });
    }

    function loadAutomation() {
        api.get('/api/pm-agent-automation').then((res) => setAutomation(res.data));
    }

    useEffect(load, [statusFilter, agentFilter]);
    useEffect(loadAutomation, []);

    async function toggleApproval(task) {
        await api.post(`/api/project-tasks/${task.id}/${task.approved_for_dev ? 'unapprove' : 'approve'}`);
        load();
    }

    async function toggleAutomation() {
        setAutomationBusy(true);
        setAutomationError('');
        try {
            const res = await api.post(`/api/pm-agent-automation/${automation.enabled ? 'disable' : 'enable'}`);
            setAutomation(res.data);
        } catch (err) {
            setAutomationError(err.response?.data?.message || t('automation_error_generic'));
        } finally {
            setAutomationBusy(false);
        }
    }

    const agents = [...new Set(tasks.map((t) => t.agent_name))].sort();

    return (
        <div>
            <h1 className="mb-2 font-sans text-2xl font-semibold text-[var(--tm-text)]">{t('progress_title')}</h1>
            <p className="mb-4 text-sm text-[var(--tm-text-muted)]">{t('progress_hint')}</p>

            {automation && (
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[var(--tm-border)] bg-[var(--tm-surface-1)] p-4">
                    <div>
                        <p className="text-sm font-medium text-[var(--tm-text)]">{t('automation_title')}</p>
                        {automation.configured ? (
                            <p className="mt-0.5 text-xs text-[var(--tm-text-muted)]">
                                {automation.enabled === null
                                    ? (automation.message || t('automation_error_generic'))
                                    : automation.enabled
                                        ? t('automation_status_enabled')
                                        : t('automation_status_disabled')}
                            </p>
                        ) : (
                            <p className="mt-0.5 text-xs text-[var(--tm-text-muted)]">{t('automation_not_configured')}</p>
                        )}
                        {automationError && (
                            <p role="alert" className="mt-0.5 text-xs text-red-400">{automationError}</p>
                        )}
                    </div>
                    {automation.configured && automation.enabled !== null && (
                        <button
                            onClick={toggleAutomation}
                            disabled={automationBusy}
                            className={
                                automation.enabled
                                    ? `rounded-md border border-[var(--tm-border-strong)] px-4 py-1.5 text-sm text-[var(--tm-text)] hover:border-[var(--tm-accent)] disabled:cursor-not-allowed disabled:opacity-50 ${FOCUS_RING}`
                                    : `rounded-md bg-[var(--tm-accent-strong)] px-4 py-1.5 text-sm text-white hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50 ${FOCUS_RING}`
                            }
                        >
                            {automation.enabled ? t('automation_disable') : t('automation_enable')}
                        </button>
                    )}
                </div>
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {STATUSES.map((s) => (
                    <button
                        key={s}
                        onClick={() => setStatusFilter(statusFilter === s ? '' : s)}
                        className={`rounded-lg border bg-[var(--tm-surface-1)] p-3 text-left transition-colors hover:bg-[var(--tm-surface-2)] ${FOCUS_RING} ${
                            statusFilter === s ? 'border-[var(--tm-accent)]' : 'border-[var(--tm-border)]'
                        }`}
                    >
                        <p className="font-sans text-2xl font-semibold text-[var(--tm-text)]">{counts[s] ?? 0}</p>
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
                    className={`rounded-md border border-[var(--tm-border-strong)] bg-[var(--tm-surface-1)] px-3 py-1.5 text-sm text-[var(--tm-text)] ${FOCUS_RING}`}
                >
                    <option value="">{t('progress_all_agents')}</option>
                    {agents.map((a) => (
                        <option key={a} value={a}>{a}</option>
                    ))}
                </select>
                {(statusFilter || agentFilter) && (
                    <button
                        onClick={() => { setStatusFilter(''); setAgentFilter(''); }}
                        className={`rounded text-sm text-[var(--tm-accent)] underline hover:no-underline ${FOCUS_RING}`}
                    >
                        {t('progress_clear_filters')}
                    </button>
                )}
            </div>

            <div className="overflow-x-auto rounded-lg border border-[var(--tm-border)] bg-[var(--tm-surface-1)]">
                <table className="w-full text-sm">
                    <thead className="bg-[var(--tm-surface-2)] text-left text-[var(--tm-text-muted)]">
                        <tr>
                            <th className="px-4 py-2">{t('progress_col_task')}</th>
                            <th className="px-4 py-2">{t('progress_col_agent')}</th>
                            <th className="px-4 py-2">{t('progress_col_status')}</th>
                            <th className="px-4 py-2">{t('progress_col_approval')}</th>
                            <th className="px-4 py-2">{t('progress_col_evidence')}</th>
                            <th className="px-4 py-2">{t('progress_col_updated')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tasks.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-6 text-center text-[var(--tm-text-muted)]">
                                    {t('progress_no_tasks')}
                                </td>
                            </tr>
                        )}
                        {tasks.map((task) => (
                            <tr key={task.id} className="border-t border-[var(--tm-border)] align-top hover:bg-[var(--tm-surface-2)]/60">
                                <td className="max-w-sm px-4 py-3">
                                    <p className="font-medium text-[var(--tm-text)]">{task.title}</p>
                                    {task.description && <p className="mt-1 text-xs text-[var(--tm-text-muted)]">{task.description}</p>}
                                    {task.status === 'blocked' && task.blocked_reason && (
                                        <p className="mt-1 text-xs text-red-400">{task.blocked_reason}</p>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap text-[var(--tm-text)]">{task.agent_name}</td>
                                <td className="px-4 py-3">
                                    <span className={`inline-block rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${STATUS_STYLES[task.status]}`}>
                                        {t(`progress_status_${task.status}`)}
                                    </span>
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {task.status === 'todo' ? (
                                        <button
                                            onClick={() => toggleApproval(task)}
                                            className={
                                                task.approved_for_dev
                                                    ? `rounded-full bg-green-900/40 px-2 py-0.5 text-xs text-green-300 hover:bg-green-900/60 ${FOCUS_RING}`
                                                    : `rounded-full border border-[var(--tm-border-strong)] px-2 py-0.5 text-xs text-[var(--tm-text-muted)] hover:border-[var(--tm-accent)] hover:text-[var(--tm-text)] ${FOCUS_RING}`
                                            }
                                        >
                                            {task.approved_for_dev ? t('progress_approved_badge') : t('progress_approve_for_dev')}
                                        </button>
                                    ) : (
                                        task.approved_for_dev && (
                                            <span className="inline-block rounded-full bg-green-900/40 px-2 py-0.5 text-xs text-green-300">
                                                {t('progress_approved_badge')}
                                            </span>
                                        )
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        {task.screenshot_url && (
                                            <button onClick={() => setLightbox(task.screenshot_url)} className={`rounded ${FOCUS_RING}`}>
                                                <img
                                                    src={task.screenshot_url}
                                                    alt={task.title}
                                                    className="h-12 w-12 rounded border border-[var(--tm-border)] object-cover object-top"
                                                />
                                            </button>
                                        )}
                                        {task.commit_sha && (
                                            <a
                                                href={`https://github.com/guykats/Tshirt-Store/commit/${task.commit_sha}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className={`rounded font-mono text-xs text-[var(--tm-accent)] hover:underline ${FOCUS_RING}`}
                                            >
                                                {task.commit_sha.slice(0, 7)}
                                            </a>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-xs whitespace-nowrap text-[var(--tm-text-muted)]">
                                    {new Date(task.updated_at).toLocaleDateString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {lightbox && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
                    onClick={() => setLightbox(null)}
                >
                    <img src={lightbox} alt="" className="max-h-full max-w-full rounded shadow-lg" />
                </div>
            )}
        </div>
    );
}
