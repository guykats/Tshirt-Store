import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

// The full set of event_type values SystemEvent::log() is called with across the
// app (OrderController, DesignController, CheckoutController, EpicController,
// TestimonialController, Admin\Product*Controller, BackupDatabase, etc.) — kept as
// a flat list here rather than fetched from the server, since it's effectively a
// fixed enum defined in application code, not data.
const EVENT_TYPES = [
    'order.paid',
    'order.approved',
    'order.status_advanced',
    'order.cancelled',
    'order.refunded',
    'design.approved',
    'design.rejected',
    'epic.approved',
    'epic.rejected',
    'epic.delayed',
    'project_task.approved',
    'project_task.unapproved',
    'testimonial.created',
    'testimonial.updated',
    'testimonial.deleted',
    'product.created',
    'product.updated',
    'product.deleted',
    'product_variant.created',
    'product_variant.updated',
    'product_variant.deleted',
    'product_image.created',
    'product_image.updated',
    'product_image.reordered',
    'product_image.deleted',
    'site_settings.updated',
    'agent_status.updated',
    'backup.completed',
    'backup.rotated',
    'backup.failed',
];

const ACTOR_TYPES = ['user', 'system'];

export default function AuditLog() {
    const { t } = useTranslation();
    const [searchParams, setSearchParams] = useSearchParams();
    const [events, setEvents] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const page = Number(searchParams.get('page')) || 1;
    const eventType = searchParams.get('event_type') || '';
    const actorType = searchParams.get('actor_type') || '';
    const dateFrom = searchParams.get('date_from') || '';
    const dateTo = searchParams.get('date_to') || '';
    const search = searchParams.get('search') || '';
    const [searchInput, setSearchInput] = useState(search);
    const debounceRef = useRef(null);

    useDocumentMeta(t('meta_audit_log_title', { app: t('app_name') }));

    useEffect(() => {
        setLoading(true);
        setError(null);
        api.get('/api/system-events', {
            params: {
                page,
                event_type: eventType || undefined,
                actor_type: actorType || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                search: search || undefined,
            },
        })
            .then((res) => {
                setEvents(res.data.data);
                setMeta(res.data.meta);
            })
            .catch(() => setError(t('audit_log_error')))
            .finally(() => setLoading(false));
    }, [page, eventType, actorType, dateFrom, dateTo, search]);

    useEffect(() => {
        setSearchInput(search);
    }, [search]);

    function updateParams(next) {
        const params = { event_type: eventType, actor_type: actorType, date_from: dateFrom, date_to: dateTo, search, page: String(page), ...next };
        Object.keys(params).forEach((key) => {
            if (!params[key] || (key === 'page' && params[key] === '1')) delete params[key];
        });
        setSearchParams(params);
    }

    function handleSearchInput(value) {
        setSearchInput(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            updateParams({ search: value, page: '1' });
        }, 350);
    }

    function clearFilters() {
        setSearchParams({});
        setSearchInput('');
    }

    function goToPage(nextPage) {
        updateParams({ page: String(nextPage) });
    }

    const hasFilters = Boolean(eventType || actorType || dateFrom || dateTo || search);

    return (
        <div className="max-w-5xl">
            <h1 className="mb-2 font-serif text-2xl">{t('audit_log_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('audit_log_hint')}</p>

            <div className="mb-6 grid grid-cols-1 gap-4 rounded border border-line p-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label htmlFor="audit-log-event-type" className="mb-1 block text-xs text-ink-soft">
                        {t('audit_log_filter_event_type')}
                    </label>
                    <select
                        id="audit-log-event-type"
                        value={eventType}
                        onChange={(e) => updateParams({ event_type: e.target.value, page: '1' })}
                        className="w-full rounded border border-line px-2 py-1.5 text-sm"
                    >
                        <option value="">{t('audit_log_filter_all')}</option>
                        {EVENT_TYPES.map((type) => (
                            <option key={type} value={type}>{type}</option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="audit-log-actor-type" className="mb-1 block text-xs text-ink-soft">
                        {t('audit_log_filter_actor_type')}
                    </label>
                    <select
                        id="audit-log-actor-type"
                        value={actorType}
                        onChange={(e) => updateParams({ actor_type: e.target.value, page: '1' })}
                        className="w-full rounded border border-line px-2 py-1.5 text-sm"
                    >
                        <option value="">{t('audit_log_filter_all')}</option>
                        {ACTOR_TYPES.map((type) => (
                            <option key={type} value={type}>{t(`audit_log_actor_type_${type}`)}</option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="audit-log-date-from" className="mb-1 block text-xs text-ink-soft">
                        {t('audit_log_filter_date_from')}
                    </label>
                    <input
                        id="audit-log-date-from"
                        type="date"
                        value={dateFrom}
                        onChange={(e) => updateParams({ date_from: e.target.value, page: '1' })}
                        className="w-full rounded border border-line px-2 py-1.5 text-sm"
                    />
                </div>

                <div>
                    <label htmlFor="audit-log-date-to" className="mb-1 block text-xs text-ink-soft">
                        {t('audit_log_filter_date_to')}
                    </label>
                    <input
                        id="audit-log-date-to"
                        type="date"
                        value={dateTo}
                        onChange={(e) => updateParams({ date_to: e.target.value, page: '1' })}
                        className="w-full rounded border border-line px-2 py-1.5 text-sm"
                    />
                </div>

                <div>
                    <label htmlFor="audit-log-search" className="mb-1 block text-xs text-ink-soft">
                        {t('audit_log_filter_search')}
                    </label>
                    <input
                        id="audit-log-search"
                        type="search"
                        value={searchInput}
                        onChange={(e) => handleSearchInput(e.target.value)}
                        placeholder={t('audit_log_filter_search_placeholder')}
                        className="w-full rounded border border-line px-2 py-1.5 text-sm"
                    />
                </div>
            </div>

            {hasFilters && (
                <button
                    type="button"
                    onClick={clearFilters}
                    className="mb-4 text-sm text-ink-soft underline hover:text-ink"
                >
                    {t('audit_log_clear_filters')}
                </button>
            )}

            {error && (
                <p role="alert" className="mb-4 text-sm text-red-700">
                    {error}
                </p>
            )}

            <div className="overflow-x-auto rounded border border-line">
                <table className="w-full text-sm">
                    <thead className="bg-parchment-dim text-left">
                        <tr>
                            <th className="px-4 py-2">{t('audit_log_col_time')}</th>
                            <th className="px-4 py-2">{t('audit_log_col_event_type')}</th>
                            <th className="px-4 py-2">{t('audit_log_col_actor')}</th>
                            <th className="px-4 py-2">{t('audit_log_col_description')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!loading && events.length === 0 && (
                            <tr>
                                <td colSpan={4} className="px-4 py-6 text-center text-ink-soft">
                                    {t('audit_log_empty')}
                                </td>
                            </tr>
                        )}
                        {events.map((event) => (
                            <tr key={event.id} className="border-t border-line align-top">
                                <td className="px-4 py-3 whitespace-nowrap text-xs text-ink-soft">
                                    {new Date(event.created_at).toLocaleString()}
                                </td>
                                <td className="px-4 py-3 font-mono text-xs">{event.event_type}</td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {event.actor_name || <span className="text-ink-soft">—</span>}
                                    <span className="ml-1 text-xs text-ink-soft">
                                        ({t(`audit_log_actor_type_${event.actor_type}`, { defaultValue: event.actor_type })})
                                    </span>
                                </td>
                                <td className="px-4 py-3">{event.description}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {meta.last_page > 1 && (
                <div className="mt-6 flex items-center justify-center gap-4">
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page - 1)}
                        disabled={meta.current_page <= 1}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('audit_log_previous')}
                    </button>
                    <span className="text-sm text-ink-soft">
                        {t('audit_log_page_of', { current: meta.current_page, last: meta.last_page })}
                    </span>
                    <button
                        type="button"
                        onClick={() => goToPage(meta.current_page + 1)}
                        disabled={meta.current_page >= meta.last_page}
                        className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        {t('audit_log_next')}
                    </button>
                </div>
            )}
        </div>
    );
}
