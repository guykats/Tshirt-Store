import { useTranslation } from 'react-i18next';

export function SkeletonBlock({ className = '' }) {
    return <div className={`animate-pulse rounded bg-parchment-dim ${className}`} />;
}

// Both skeletons below mirror RouteLoading.jsx's role="status" aria-live="polite"
// pattern: the skeleton blocks themselves are decorative (aria-hidden), so a
// screen reader user would otherwise get total silence during the fetch. The
// sr-only label gives them an announced "loading" state, and because the
// status region persists across the loading -> loaded transition, the browser
// also announces when it's removed/replaced with real content.
export function CatalogSkeleton({ count = 6 }) {
    const { t } = useTranslation();

    return (
        <div role="status" aria-live="polite">
            <span className="sr-only">{t('catalog_loading')}</span>
            <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" aria-hidden="true">
                {Array.from({ length: count }).map((_, i) => (
                    <div key={i}>
                        <SkeletonBlock className="aspect-square w-full" />
                        <SkeletonBlock className="mt-4 h-5 w-2/3" />
                        <SkeletonBlock className="mt-2 h-4 w-1/3" />
                    </div>
                ))}
            </div>
        </div>
    );
}

export function ProductDetailSkeleton() {
    const { t } = useTranslation();

    return (
        <div role="status" aria-live="polite">
            <span className="sr-only">{t('product_detail_loading')}</span>
            <div className="grid grid-cols-1 gap-10 md:grid-cols-2" aria-hidden="true">
                <SkeletonBlock className="aspect-square rounded" />
                <div>
                    <SkeletonBlock className="h-8 w-3/4" />
                    <SkeletonBlock className="mt-3 h-5 w-1/4" />
                    <SkeletonBlock className="mt-6 h-4 w-full" />
                    <SkeletonBlock className="mt-2 h-4 w-5/6" />
                    <SkeletonBlock className="mt-10 h-12 w-full" />
                </div>
            </div>
        </div>
    );
}
