import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const EPIC_STATUS_STYLES = {
    proposed: 'bg-amber-900/40 text-amber-300',
    approved: 'bg-green-900/40 text-green-300',
    rejected: 'bg-red-900/40 text-red-300',
};

// Mirrors the exact precedence EpicController::index computes build_status
// with (blocked > in_progress > todo > done) and the same tile-row + select
// filter vocabulary as ProjectProgress.jsx's STATUSES/STATUS_STYLES — this
// is a one-level-up rollup of approved epics, not the Board itself, so it
// deliberately omits the Board's 'approved' pseudo-status.
const BUILD_STATUSES = ['blocked', 'in_progress', 'todo', 'done'];
const BUILD_STATUS_STYLES = {
    todo: 'bg-white/[0.07] text-[var(--tm-text-muted)]',
    in_progress: 'bg-blue-900/40 text-blue-300',
    blocked: 'bg-red-900/40 text-red-300',
    done: 'bg-green-900/40 text-green-300',
};

// Explicit keyboard-focus ring for this dark theme — see TeamManagementNav
// for the same rationale (the UA default outline reads poorly on near-black).
const FOCUS_RING =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--tm-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--tm-surface-1)]';

export default function Epics() {
    const { t } = useTranslation();
    const [epics, setEpics] = useState([]);
    const [showDecidedEpics, setShowDecidedEpics] = useState(false);
    const [buildStatusFilter, setBuildStatusFilter] = useState('');

    useDocumentMeta(t('meta_epics_title', { app: t('app_name') }));

    function loadEpics() {
        api.get('/api/epics').then((res) => setEpics(res.data.data));
    }

    useEffect(loadEpics, []);

    async function decideEpic(epicId, action) {
        await api.post(`/api/epics/${epicId}/${action}`);
        loadEpics();
    }

    // Same "decided epics" toggle as before, computed first so build_status
    // tile counts and the row's visibility reflect what's actually on
    // screen (e.g. stay hidden while only proposed epics are shown).
    const decidedFilteredEpics = epics.filter((e) => showDecidedEpics || e.status === 'proposed');
    const approvedInView = decidedFilteredEpics.filter((e) => e.status === 'approved');
    const showBuildStatusRow = approvedInView.length > 0;
    const buildStatusCounts = BUILD_STATUSES.reduce((acc, s) => {
        acc[s] = approvedInView.filter((e) => e.build_status === s).length;
        return acc;
    }, {});
    // The build_status filter only ever narrows approved epics — proposed
    // and rejected epics keep showing exactly what they show today.
    const visibleEpics = decidedFilteredEpics.filter(
        (e) => e.status !== 'approved' || !buildStatusFilter || e.build_status === buildStatusFilter,
    );

    return (
        <div>
            <h1 className="mb-2 font-sans text-2xl font-semibold text-[var(--tm-text)]">{t('epics_title')}</h1>
            <div className="mb-1 flex items-center justify-between">
                <p className="text-sm text-[var(--tm-text-muted)]">{t('epics_hint')}</p>
                <button
                    onClick={() => setShowDecidedEpics((v) => !v)}
                    className={`shrink-0 rounded text-sm text-[var(--tm-accent)] hover:underline ${FOCUS_RING}`}
                >
                    {showDecidedEpics ? t('epics_hide_decided') : t('epics_show_decided')}
                </button>
            </div>

            {showBuildStatusRow && (
                <div className="mt-4 mb-2">
                    <p className="mb-2 text-xs font-medium tracking-wide text-[var(--tm-text-muted)] uppercase">
                        {t('epics_build_status_heading')}
                    </p>
                    <p className="mb-3 text-sm text-[var(--tm-text-muted)]">{t('epics_build_status_hint')}</p>
                    <div className="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {BUILD_STATUSES.map((s) => (
                            <button
                                key={s}
                                onClick={() => setBuildStatusFilter(buildStatusFilter === s ? '' : s)}
                                className={`rounded-lg border bg-[var(--tm-surface-1)] p-3 text-left transition-colors hover:bg-[var(--tm-surface-2)] ${FOCUS_RING} ${
                                    buildStatusFilter === s ? 'border-[var(--tm-accent)]' : 'border-[var(--tm-border)]'
                                }`}
                            >
                                <p className="font-sans text-2xl font-semibold text-[var(--tm-text)]">{buildStatusCounts[s] ?? 0}</p>
                                <p className={`mt-1 inline-block rounded-full px-2 py-0.5 text-xs ${BUILD_STATUS_STYLES[s]}`}>
                                    {t(`epics_build_status_${s}`)}
                                </p>
                            </button>
                        ))}
                    </div>
                    <label htmlFor="epics-build-status-filter" className="sr-only">
                        {t('epics_build_status_heading')}
                    </label>
                    <select
                        id="epics-build-status-filter"
                        value={buildStatusFilter}
                        onChange={(e) => setBuildStatusFilter(e.target.value)}
                        className={`rounded-md border border-[var(--tm-border-strong)] bg-[var(--tm-surface-1)] px-3 py-1.5 text-sm text-[var(--tm-text)] ${FOCUS_RING}`}
                    >
                        <option value="">{t('epics_build_status_all')}</option>
                        {BUILD_STATUSES.map((s) => (
                            <option key={s} value={s}>{t(`epics_build_status_${s}`)}</option>
                        ))}
                    </select>
                </div>
            )}

            {visibleEpics.length === 0 && (
                <p className="mt-6 rounded-lg border border-[var(--tm-border)] bg-[var(--tm-surface-1)] p-4 text-sm text-[var(--tm-text-muted)]">
                    {t('epics_empty')}
                </p>
            )}

            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                {visibleEpics.map((epic) => (
                    <div
                        key={epic.id}
                        className="flex flex-col rounded-lg border border-[var(--tm-border)] bg-[var(--tm-surface-1)] p-4"
                    >
                        <div className="mb-2 flex items-start justify-between gap-2">
                            <p className="font-sans text-base font-semibold text-[var(--tm-text)]">{epic.title}</p>
                            <div className="flex shrink-0 flex-col items-end gap-1">
                                <span className={`rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${EPIC_STATUS_STYLES[epic.status]}`}>
                                    {t(`epics_status_${epic.status}`)}
                                </span>
                                {epic.status === 'approved' && epic.build_status && (
                                    <span className={`rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${BUILD_STATUS_STYLES[epic.build_status]}`}>
                                        {t(`epics_build_status_${epic.build_status}`)}
                                    </span>
                                )}
                            </div>
                        </div>
                        <p className="mb-3 flex-1 text-sm text-[var(--tm-text-muted)]">{epic.description}</p>
                        <div className="mb-3 flex items-center justify-between text-xs text-[var(--tm-text-muted)]">
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
                                    className={`flex-1 rounded-md bg-[var(--tm-accent-strong)] px-3 py-1.5 text-sm text-white hover:brightness-110 ${FOCUS_RING}`}
                                >
                                    {t('epics_choose')}
                                </button>
                                <button
                                    onClick={() => decideEpic(epic.id, 'reject')}
                                    className={`flex-1 rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-500 ${FOCUS_RING}`}
                                >
                                    {t('epics_reject')}
                                </button>
                                <button
                                    onClick={() => decideEpic(epic.id, 'delay')}
                                    className={`flex-1 rounded-md border border-[var(--tm-border-strong)] px-3 py-1.5 text-sm text-[var(--tm-text)] hover:border-[var(--tm-accent)] ${FOCUS_RING}`}
                                >
                                    {t('epics_delay')}
                                </button>
                            </div>
                        ) : (
                            epic.decided_by && (
                                <p className="text-xs text-[var(--tm-text-muted)]">{t('epics_decided_by', { name: epic.decided_by })}</p>
                            )
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
