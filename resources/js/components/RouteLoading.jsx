import { useTranslation } from 'react-i18next';

// Suspense fallback for lazy-loaded route chunks (see App.jsx). Route bundles are
// small and same-origin, so this is only ever visible for a brief flash on
// client-side navigation — it exists mainly so screen reader users get an
// announced status instead of silence while the next page's chunk downloads.
export default function RouteLoading() {
    const { t } = useTranslation();

    return (
        <div className="px-6 py-24 text-center text-sm text-ink-soft" role="status" aria-live="polite">
            {t('route_loading')}
        </div>
    );
}
