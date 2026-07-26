import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

const EPIC_STATUS_STYLES = {
    proposed: 'bg-amber-900/40 text-amber-300',
    approved: 'bg-green-900/40 text-green-300',
    rejected: 'bg-red-900/40 text-red-300',
};

// Explicit keyboard-focus ring for this dark theme — see TeamManagementNav
// for the same rationale (the UA default outline reads poorly on near-black).
const FOCUS_RING =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--tm-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--tm-surface-1)]';

export default function Epics() {
    const { t } = useTranslation();
    const [epics, setEpics] = useState([]);
    const [showDecidedEpics, setShowDecidedEpics] = useState(false);

    useDocumentMeta(t('meta_epics_title', { app: t('app_name') }));

    function loadEpics() {
        api.get('/api/epics').then((res) => setEpics(res.data.data));
    }

    useEffect(loadEpics, []);

    async function decideEpic(epicId, action) {
        await api.post(`/api/epics/${epicId}/${action}`);
        loadEpics();
    }

    const visibleEpics = epics.filter((e) => showDecidedEpics || e.status === 'proposed');

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
                            <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs whitespace-nowrap ${EPIC_STATUS_STYLES[epic.status]}`}>
                                {t(`epics_status_${epic.status}`)}
                            </span>
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
