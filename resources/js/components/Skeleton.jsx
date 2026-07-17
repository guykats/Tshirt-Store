export function SkeletonBlock({ className = '' }) {
    return <div className={`animate-pulse rounded bg-parchment-dim ${className}`} />;
}

export function CatalogSkeleton({ count = 6 }) {
    return (
        <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i}>
                    <SkeletonBlock className="aspect-square w-full" />
                    <SkeletonBlock className="mt-4 h-5 w-2/3" />
                    <SkeletonBlock className="mt-2 h-4 w-1/3" />
                </div>
            ))}
        </div>
    );
}

export function ProductDetailSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-10 md:grid-cols-2">
            <SkeletonBlock className="aspect-square rounded" />
            <div>
                <SkeletonBlock className="h-8 w-3/4" />
                <SkeletonBlock className="mt-3 h-5 w-1/4" />
                <SkeletonBlock className="mt-6 h-4 w-full" />
                <SkeletonBlock className="mt-2 h-4 w-5/6" />
                <SkeletonBlock className="mt-10 h-12 w-full" />
            </div>
        </div>
    );
}
