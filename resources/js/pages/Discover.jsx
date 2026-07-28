import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { formatDate } from '../lib/formatDate';

// Focus-visible ring shared by Keep/Discard/Generate now so every actionable
// control here has a clearly visible keyboard focus state, matching the
// brass/ink storefront palette rather than borrowing Team Management's
// dark-theme --tm-accent ring (see TeamManagementNav.jsx) which isn't in
// scope for this light-themed admin tab.
const FOCUS_RING = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brass focus-visible:ring-offset-2 focus-visible:ring-offset-parchment';

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
export default function Discover() {
    const { t, i18n } = useTranslation();

    useDocumentMeta(t('meta_discover_title', { app: t('app_name') }));

    const [suggestions, setSuggestions] = useState([]);
    const [batchDate, setBatchDate] = useState(null);
    const [loading, setLoading] = useState(true);
    const [generating, setGenerating] = useState(false);
    const [statusMessage, setStatusMessage] = useState('');
    const [error, setError] = useState(null);
    const [actioningId, setActioningId] = useState(null);

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
            await api.post(`/api/design-suggestions/${suggestion.id}/keep`);
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
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
            await api.post(`/api/design-suggestions/${suggestion.id}/discard`);
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
            setStatusMessage(t('discover_discarded', { motif: t(motifLabelKey(suggestion.motif)) }));
        } catch {
            setSuggestions((current) => current.filter((s) => s.id !== suggestion.id));
            setStatusMessage(t('discover_already_changed'));
        } finally {
            setActioningId(null);
        }
    }

    return (
        <div className="max-w-5xl">
            <h1 className="mb-2 font-serif text-2xl">{t('discover_title')}</h1>
            <p className="mb-1 text-sm text-ink-soft">{t('discover_hint')}</p>
            <p className="mb-6 text-sm text-ink-soft">{t('discover_replacement_warning')}</p>

            {batchDate && (
                <p className="mb-4 text-xs text-ink-soft">
                    {t('discover_batch_date', { date: formatDate(batchDate, i18n.language) })}
                </p>
            )}

            <div className="mb-8 flex flex-wrap items-center gap-4">
                <button
                    type="button"
                    onClick={handleGenerate}
                    disabled={generating}
                    className={`rounded bg-ink px-4 py-2 text-sm tracking-wide text-parchment uppercase disabled:opacity-60 ${FOCUS_RING}`}
                >
                    {generating ? t('discover_generating_button') : t('discover_generate_button')}
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

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {suggestions.map((suggestion) => {
                    const motifLabel = t(motifLabelKey(suggestion.motif));
                    const isActioning = actioningId === suggestion.id;

                    return (
                        <div key={suggestion.id} className="flex flex-col items-center rounded border border-line p-4">
                            <DesignArt
                                motif={suggestion.motif}
                                label={t('discover_card_art_label', { motif: motifLabel })}
                                className="mb-4 h-32 w-32 rounded"
                            />
                            <p className="mb-3 font-medium">{motifLabel}</p>
                            <div className="flex gap-2">
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
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
