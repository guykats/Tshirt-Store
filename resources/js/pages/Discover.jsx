import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { formatDate } from '../lib/formatDate';

// Focus-visible ring shared by Keep/Discard/Publish/Generate now so every
// actionable control here has a clearly visible keyboard focus state,
// matching the brass/ink storefront palette rather than borrowing Team
// Management's dark-theme --tm-accent ring (see TeamManagementNav.jsx) which
// isn't in scope for this light-themed admin tab.
const FOCUS_RING = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brass focus-visible:ring-offset-2 focus-visible:ring-offset-parchment';

// The four design_suggestions.status values, in the order the stat/filter
// bar (and each card's border/badge treatment) presents them — pending is
// the incoming queue, kept is a checkpoint awaiting an explicit publish,
// published/discarded are terminal. Mirrors the interaction convention of
// ProjectProgress.jsx's stat tiles (clickable, toggles a filter, count +
// colored status pill) rather than inventing a new one, just re-themed for
// this page's light parchment palette instead of Team Management's dark one.
const STATUSES = ['pending', 'kept', 'published', 'discarded'];

// bg-*-100/text-*-800 tints on parchment, same convention the rest of the
// light-themed admin pages use for status pills (see e.g. Orders.jsx).
const STATUS_BADGE_STYLES = {
    pending: 'bg-amber-100 text-amber-800',
    kept: 'bg-blue-100 text-blue-800',
    published: 'bg-green-100 text-green-800',
    discarded: 'bg-red-100 text-red-800',
};

// Left-border accent per status so a kept-but-unpublished card stays
// visually distinguishable from the grid at a glance, not just via the
// small badge text.
const STATUS_BORDER_STYLES = {
    pending: 'border-l-4 border-l-amber-500',
    kept: 'border-l-4 border-l-blue-500',
    published: 'border-l-4 border-l-green-600',
    discarded: 'border-l-4 border-l-red-600',
};

function motifLabelKey(motif) {
    return `design_settings_motif_${motif.replace(/-/g, '_')}`;
}

// Store admin's daily "Discover" review queue for the nightly (or on-demand)
// AI-generated design_suggestions batch — see the backend task on this same
// epic (design_suggestions table + DesignSuggestionController). Deliberately
// mirrors Dashboard.jsx's pending-designs review list (DesignArt preview +
// Keep/Discard-style approve/reject buttons) since this is the same kind of
// "look at a small AI-produced batch and decide" workflow, just for
// suggestions rather than already-promoted designs.
//
// This is now a small management system rather than a one-shot approval
// queue (Discover Tab 2.0 / publish-gate epic): keeping a suggestion is just
// a checkpoint (status=kept) that stays visible in the grid, and publishing
// it into the real Pending Designs pipeline is a separate, explicit later
// action (per-card Publish, or a "Publish all kept" bulk action) rather than
// something Keep did automatically.
export default function Discover() {
    const { t, i18n } = useTranslation();

    useDocumentMeta(t('meta_discover_title', { app: t('app_name') }));

    const [suggestions, setSuggestions] = useState([]);
    const [batchDate, setBatchDate] = useState(null);
    const [loading, setLoading] = useState(true);
    const [generating, setGenerating] = useState(false);
    const [publishingAll, setPublishingAll] = useState(false);
    const [statusMessage, setStatusMessage] = useState('');
    const [error, setError] = useState(null);
    const [actioningId, setActioningId] = useState(null);
    const [statusFilter, setStatusFilter] = useState('');

    function loadLatestBatch() {
        setLoading(true);
        setError(null);
        setStatusMessage(t('discover_loading'));

        return api.get('/api/design-suggestions')
            .then((res) => {
                const data = res.data.data;
                setSuggestions(data);
                setBatchDate(data[0]?.batch_date ?? null);
                setStatusMessage(t('discover_loaded', { count: data.length }));
            })
            .catch(() => {
                setError(t('discover_load_error'));
                setStatusMessage('');
            })
            .finally(() => setLoading(false));
    }

    useEffect(() => {
        loadLatestBatch();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function handleGenerate() {
        setGenerating(true);
        setError(null);
        setStatusMessage(t('discover_generating'));

        try {
            const res = await api.post('/api/design-suggestions/generate');
            const data = res.data.data;
            setSuggestions(data);
            setBatchDate(data[0]?.batch_date ?? null);
            setStatusFilter('');
            setStatusMessage(t('discover_generated', { count: data.length }));
        } catch {
            setError(t('discover_generate_error'));
            setStatusMessage('');
        } finally {
            setGenerating(false);
        }
    }

    async function handleKeep(suggestion) {
        setActioningId(suggestion.id);
        setError(null);
        try {
            const res = await api.post(`/api/design-suggestions/${suggestion.id}/keep`);
            const updated = res.data.data;
            setSuggestions((current) => current.map((s) => (
                s.id === suggestion.id ? { ...s, ...updated } : s
            )));
            setStatusMessage(t('discover_kept', { motif: t(motifLabelKey(suggestion.motif)) }));
        } catch {
            // A 422 here means another admin (or the nightly job) already
            // changed this suggestion's status out from under us — just drop
            // it from the grid rather than surfacing a scary error for a
            // perfectly normal race.
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
            setStatusMessage(t('discover_already_changed'));
        } finally {
            setActioningId(null);
        }
    }

    async function handleDiscard(suggestion) {
        setActioningId(suggestion.id);
        setError(null);
        try {
            const res = await api.post(`/api/design-suggestions/${suggestion.id}/discard`);
            const updated = res.data.data;
            setSuggestions((current) => current.map((s) => (
                s.id === suggestion.id ? { ...s, ...updated } : s
            )));
            setStatusMessage(t('discover_discarded', { motif: t(motifLabelKey(suggestion.motif)) }));
        } catch {
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
            setStatusMessage(t('discover_already_changed'));
        } finally {
            setActioningId(null);
        }
    }

    // Promotes one kept suggestion into a real Design row (DesignSuggestionController::
    // publish). The response is a Design, not a DesignSuggestion — there's no
    // suggestion payload to merge back in, so this just flips the local card's
    // own status to 'published' rather than trying to read it off the response.
    async function handlePublish(suggestion) {
        setActioningId(suggestion.id);
        setError(null);
        try {
            await api.post(`/api/design-suggestions/${suggestion.id}/publish`);
            setSuggestions((current) => current.map((s) => (
                s.id === suggestion.id ? { ...s, status: 'published' } : s
            )));
            setStatusMessage(t('discover_published', { motif: t(motifLabelKey(suggestion.motif)) }));
        } catch {
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
            setStatusMessage(t('discover_already_changed'));
        } finally {
            setActioningId(null);
        }
    }

    // Bulk-publishes every currently kept suggestion (DesignSuggestionController::
    // publishAll operates on all kept suggestions in the latest batch). The
    // response is a collection of Designs with no suggestion linkage, so —
    // matching the single-publish handling above — this optimistically flips
    // every locally-kept card to 'published' rather than trying to reconcile
    // by id.
    async function handlePublishAll() {
        setPublishingAll(true);
        setError(null);
        try {
            const res = await api.post('/api/design-suggestions/publish-all');
            const publishedCount = res.data.data.length;
            setSuggestions((current) => current.map((s) => (
                s.status === 'kept' ? { ...s, status: 'published' } : s
            )));
            setStatusMessage(t('discover_published_all', { count: publishedCount }));
        } catch {
            setError(t('discover_publish_all_error'));
            setStatusMessage('');
        } finally {
            setPublishingAll(false);
        }
    }

    const counts = useMemo(() => (
        STATUSES.reduce((acc, status) => {
            acc[status] = suggestions.filter((s) => s.status === status).length;
            return acc;
        }, {})
    ), [suggestions]);

    const keptCount = counts.kept ?? 0;

    const visibleSuggestions = statusFilter
        ? suggestions.filter((s) => s.status === statusFilter)
        : suggestions;

    function toggleStatusFilter(status) {
        setStatusFilter((current) => (current === status ? '' : status));
    }

    return (
        <div>
            <h1 className="mb-2 font-serif text-2xl">{t('discover_title')}</h1>
            <p className="mb-1 text-sm text-ink-soft">{t('discover_hint')}</p>
            <p className="mb-6 text-sm text-ink-soft">{t('discover_replacement_warning')}</p>

            {batchDate && (
                <p className="mb-4 text-xs text-ink-soft">
                    {t('discover_batch_date', { date: formatDate(batchDate, i18n.language) })}
                </p>
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {STATUSES.map((status) => (
                    <button
                        key={status}
                        type="button"
                        onClick={() => toggleStatusFilter(status)}
                        aria-pressed={statusFilter === status}
                        className={`rounded-lg border p-3 text-left transition-colors hover:bg-parchment-dim ${FOCUS_RING} ${
                            statusFilter === status ? 'border-brass' : 'border-line'
                        }`}
                    >
                        <p className="font-serif text-2xl">{counts[status] ?? 0}</p>
                        <p className={`mt-1 inline-block rounded-full px-2 py-0.5 text-xs ${STATUS_BADGE_STYLES[status]}`}>
                            {t(`discover_status_${status}`)}
                        </p>
                    </button>
                ))}
            </div>

            <div className="mb-8 flex flex-wrap items-center gap-4">
                <button
                    type="button"
                    onClick={handleGenerate}
                    disabled={generating}
                    className={`rounded bg-ink px-4 py-2 text-sm tracking-wide text-parchment uppercase disabled:opacity-60 ${FOCUS_RING}`}
                >
                    {generating ? t('discover_generating_button') : t('discover_generate_button')}
                </button>
                <button
                    type="button"
                    onClick={handlePublishAll}
                    disabled={keptCount === 0 || publishingAll}
                    className={`rounded bg-brass px-4 py-2 text-sm tracking-wide text-parchment uppercase disabled:opacity-60 ${FOCUS_RING}`}
                >
                    {publishingAll
                        ? t('discover_publishing_all_button')
                        : t('discover_publish_all_button', { count: keptCount })}
                </button>
                <p role="status" aria-live="polite" className="text-sm text-ink-soft">
                    {statusMessage}
                </p>
            </div>

            {error && (
                <p role="alert" className="mb-4 text-sm text-red-700">
                    {error}
                </p>
            )}

            {!loading && suggestions.length === 0 && !error && (
                <p className="text-ink-soft">{t('discover_empty')}</p>
            )}

            {!loading && suggestions.length > 0 && visibleSuggestions.length === 0 && (
                <p className="text-ink-soft">{t('discover_filter_empty')}</p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {visibleSuggestions.map((suggestion) => {
                    const motifLabel = t(motifLabelKey(suggestion.motif));
                    const isActioning = actioningId === suggestion.id;

                    return (
                        <div
                            key={suggestion.id}
                            className={`flex flex-col items-center rounded border border-line p-4 ${STATUS_BORDER_STYLES[suggestion.status]}`}
                        >
                            <span className={`mb-3 inline-block rounded-full px-2 py-0.5 text-xs ${STATUS_BADGE_STYLES[suggestion.status]}`}>
                                {t(`discover_status_${suggestion.status}`)}
                            </span>
                            <DesignArt
                                motif={suggestion.motif}
                                label={t('discover_card_art_label', { motif: motifLabel })}
                                className="mb-4 h-32 w-32 rounded"
                            />
                            <p className="mb-3 font-medium">{motifLabel}</p>
                            <div className="flex gap-2">
                                {suggestion.status === 'pending' && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => handleKeep(suggestion)}
                                            disabled={isActioning}
                                            className={`rounded bg-green-600 px-3 py-1.5 text-sm text-white disabled:opacity-60 ${FOCUS_RING}`}
                                        >
                                            {t('discover_keep')}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleDiscard(suggestion)}
                                            disabled={isActioning}
                                            className={`rounded bg-red-600 px-3 py-1.5 text-sm text-white disabled:opacity-60 ${FOCUS_RING}`}
                                        >
                                            {t('discover_discard')}
                                        </button>
                                    </>
                                )}
                                {suggestion.status === 'kept' && (
                                    <button
                                        type="button"
                                        onClick={() => handlePublish(suggestion)}
                                        disabled={isActioning}
                                        className={`rounded bg-brass px-3 py-1.5 text-sm text-parchment disabled:opacity-60 ${FOCUS_RING}`}
                                    >
                                        {t('discover_publish_button')}
                                    </button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
