import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import GarmentMockup from '../components/GarmentMockup';
import WishlistButton from '../components/WishlistButton';
import { CatalogSkeleton } from '../components/Skeleton';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { useSiteSettings } from '../lib/SiteSettingsContext';
import { formatPrice } from '../lib/formatPrice';

const SORT_OPTIONS = ['newest', 'price_asc', 'price_desc'];

export default function Catalog() {
    const { t, i18n } = useTranslation();
    const { settings } = useSiteSettings();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
    const [homeStats, setHomeStats] = useState(null);
    const [testimonials, setTestimonials] = useState([]);
    const [searchParams, setSearchParams] = useSearchParams();
    const page = Number(searchParams.get('page')) || 1;
    const search = searchParams.get('search') || '';
    const sort = searchParams.get('sort') || 'newest';
    const [searchInput, setSearchInput] = useState(search);
    const debounceRef = useRef(null);

    useDocumentMeta(t('app_name'), t('meta_catalog_description'));

    useEffect(() => {
        setLoading(true);
        api.get('/api/products', { params: { page, search: search || undefined, sort } })
            .then((res) => {
                setProducts(res.data.data);
                setMeta(res.data.meta);
            })
            .finally(() => setLoading(false));
        window.scrollTo({ top: 0 });
    }, [page, search, sort]);

    // Keep the local input in sync when navigating back/forward.
    useEffect(() => {
        setSearchInput(search);
    }, [search]);

    // Real, computed investor-facing numbers (completed orders, real review average,
    // distinct shipping countries) — see App\Http\Controllers\Api\HomeStatsController.
    // Fetched once; this section doesn't change per search/sort/page interaction.
    useEffect(() => {
        api.get('/api/home-stats')
            .then((res) => setHomeStats(res.data.data))
            .catch(() => setHomeStats(null));
        api.get('/api/testimonials')
            .then((res) => setTestimonials(res.data.data))
            .catch(() => setTestimonials([]));
    }, []);

    function updateParams(next) {
        const params = { search, sort, page: String(page), ...next };
        if (!params.search) delete params.search;
        if (params.sort === 'newest') delete params.sort;
        if (params.page === '1') delete params.page;
        setSearchParams(params);
    }

    function handleSearchInput(value) {
        setSearchInput(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            updateParams({ search: value, page: '1' });
        }, 350);
    }

    function handleSortChange(value) {
        updateParams({ sort: value, page: '1' });
    }

    function goToPage(nextPage) {
        updateParams({ page: String(nextPage) });
    }

    const isSearching = search.trim().length > 0;

    const trustItems = [
        { key: 'symbolism', titleKey: 'catalog_trust_symbolism_title', descKey: 'catalog_trust_symbolism_desc' },
        { key: 'checkout', titleKey: 'catalog_trust_checkout_title', descKey: 'catalog_trust_checkout_desc' },
        { key: 'made_to_order', titleKey: 'catalog_trust_madetoorder_title', descKey: 'catalog_trust_madetoorder_desc' },
        { key: 'shipping', titleKey: 'catalog_trust_shipping_title', descKey: 'catalog_trust_shipping_desc' },
    ];

    // Admin-configurable via /dashboard/design (site_settings table); fall back to the
    // static i18n copy while settings are loading or if the request ever fails, so the
    // homepage never renders blank hero content.
    const heroTagline = (i18n.language === 'he' ? settings?.hero_tagline_he : settings?.hero_tagline_en) || t('hero_title');
    const heroSubheading = (i18n.language === 'he' ? settings?.hero_subheading_he : settings?.hero_subheading_en) || t('hero_subtitle');
    const heroMotif = settings?.hero_motif || 'star-of-david';

    // Every number here is computed from real orders/reviews (HomeStatsController),
    // not admin-typed — see the removal of stat_pieces_shipped/stat_rating/stat_countries
    // from site_settings. A null average_rating means zero reviews exist yet, so it
    // renders a qualitative "New" label instead of a fabricated number.
    const stats = homeStats
        ? [
              {
                  key: 'completed_orders',
                  value: homeStats.completed_orders.toLocaleString(i18n.language),
                  labelKey: 'home_stat_completed_orders_label',
              },
              {
                  key: 'rating',
                  value: homeStats.average_rating != null ? `${homeStats.average_rating.toFixed(1)} ★` : t('home_stat_rating_new'),
                  labelKey: 'home_stat_rating_label',
              },
              {
                  key: 'countries',
                  value: homeStats.countries_served.toLocaleString(i18n.language),
                  labelKey: 'home_stat_countries_label',
              },
          ]
        : [];

    return (
        <div>
            {/* Hero */}
            <section className="border-b border-line">
                <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:gap-16 lg:py-20">
                    <div>
                        <p className="mb-4 text-xs tracking-[0.3em] text-brass uppercase">{t('hero_eyebrow')}</p>
                        <h1 className="font-serif text-4xl leading-tight sm:text-5xl">{heroTagline}</h1>
                        <p className="mt-5 max-w-md text-ink-soft">{heroSubheading}</p>
                        <div className="mt-8 flex flex-wrap gap-4">
                            <a
                                href="#collection"
                                className="rounded bg-ink px-6 py-3 text-sm text-parchment transition-colors hover:bg-ink/90"
                            >
                                {t('catalog_cta_shop')}
                            </a>
                            <Link
                                to="/about"
                                className="rounded border border-ink px-6 py-3 text-sm transition-colors hover:bg-parchment-dim"
                            >
                                {t('catalog_cta_story')}
                            </Link>
                        </div>
                        {stats.length > 0 && (
                            <dl className="mt-10 grid grid-cols-3 gap-6 border-t border-line pt-8">
                                {stats.map((stat) => (
                                    <div key={stat.key}>
                                        <dd className="font-serif text-2xl">{stat.value}</dd>
                                        <dt className="mt-1 text-xs text-ink-soft uppercase tracking-wide">{t(stat.labelKey)}</dt>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </div>
                    <div className="mx-auto w-full max-w-sm lg:max-w-none">
                        <DesignArt
                            motif={heroMotif}
                            label={t('catalog_hero_motif_label')}
                            className="aspect-square rounded-lg"
                        />
                    </div>
                </div>
            </section>

            {/* Trust strip */}
            <section className="border-b border-line bg-parchment-dim/50">
                <div className="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-6 py-12 sm:grid-cols-2 lg:grid-cols-4">
                    {trustItems.map((item) => (
                        <div key={item.key} className="text-center sm:text-left">
                            <span aria-hidden="true" className="mb-2 inline-block text-brass">
                                ✦
                            </span>
                            <h3 className="font-serif text-base">{t(item.titleKey)}</h3>
                            <p className="mt-1 text-sm text-ink-soft">{t(item.descKey)}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Testimonials — real, admin-editable quotes (Testimonial model), kept
                separate from the reviews table's aggregated star rating above so
                nothing on a quote card could be mistaken for a computed statistic. */}
            {testimonials.length > 0 && (
                <section className="border-b border-line">
                    <div className="mx-auto max-w-6xl px-6 py-14">
                        <div className="mb-10 text-center">
                            <h2 className="font-serif text-2xl">{t('testimonials_section_title')}</h2>
                            <p className="mt-2 text-sm text-ink-soft">{t('testimonials_section_subtitle')}</p>
                        </div>
                        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            {testimonials.map((testimonial) => (
                                <figure key={testimonial.id} className="rounded border border-line bg-parchment-dim/40 p-6">
                                    <span aria-hidden="true" className="mb-3 block font-serif text-3xl text-brass">
                                        “
                                    </span>
                                    <blockquote className="text-sm leading-relaxed text-ink-soft">
                                        {i18n.language === 'he' ? testimonial.quote_he : testimonial.quote_en}
                                    </blockquote>
                                    <figcaption className="mt-4 text-sm">
                                        <span className="block font-medium">{testimonial.author_name}</span>
                                        <span className="block text-xs text-ink-soft">
                                            {i18n.language === 'he' ? testimonial.author_context_he : testimonial.author_context_en}
                                        </span>
                                    </figcaption>
                                </figure>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <div id="collection" className="mx-auto max-w-6xl px-6 py-14">
                <div className="mb-10 text-center">
                    <h2 className="font-serif text-2xl">{t('catalog_collection_heading')}</h2>
                    <p className="mt-2 text-sm text-ink-soft">{t('catalog_collection_subheading')}</p>
                </div>

                <div className="mb-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex-1">
                        <label htmlFor="catalog-search" className="sr-only">
                            {t('catalog_search_label')}
                        </label>
                        <input
                            id="catalog-search"
                            type="search"
                            value={searchInput}
                            onChange={(e) => handleSearchInput(e.target.value)}
                            placeholder={t('catalog_search_placeholder')}
                            className="w-full rounded border border-line px-4 py-2 text-sm sm:max-w-xs"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <label htmlFor="catalog-sort" className="text-sm text-ink-soft">
                            {t('catalog_sort_label')}
                        </label>
                        <select
                            id="catalog-sort"
                            value={sort}
                            onChange={(e) => handleSortChange(e.target.value)}
                            className="rounded border border-line px-3 py-2 text-sm"
                        >
                            {SORT_OPTIONS.map((option) => (
                                <option key={option} value={option}>
                                    {t(`catalog_sort_${option}`)}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {loading && <CatalogSkeleton />}

                {!loading && products.length === 0 && (
                    <p className="text-ink-soft">{isSearching ? t('catalog_no_search_results', { search }) : t('catalog_empty')}</p>
                )}

                {!loading && products.length > 0 && (
                    <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        {products.map((product) => (
                            <div key={product.id} className="group relative">
                                <Link to={`/products/${product.slug}`} className="block">
                                    <GarmentMockup
                                        motif={product.design?.mockup_url}
                                        product={product}
                                        className="aspect-square rounded transition-colors group-hover:bg-line"
                                    />
                                    <h2 className="mt-4 font-serif text-lg">{product.name}</h2>
                                    <p className="mt-1 text-sm text-ink-soft">
                                        {formatPrice(product.base_price, product.currency, i18n.language)}
                                    </p>
                                </Link>
                                <WishlistButton product={product} className="absolute top-3 right-3" />
                            </div>
                        ))}
                    </div>
                )}

                {!loading && meta.last_page > 1 && (
                    <div className="mt-14 flex items-center justify-center gap-4">
                        <button
                            onClick={() => goToPage(meta.current_page - 1)}
                            disabled={meta.current_page <= 1}
                            className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                        >
                            {t('catalog_previous')}
                        </button>
                        <span className="text-sm text-ink-soft">
                            {t('catalog_page_of', { current: meta.current_page, last: meta.last_page })}
                        </span>
                        <button
                            onClick={() => goToPage(meta.current_page + 1)}
                            disabled={meta.current_page >= meta.last_page}
                            className="rounded border border-line px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-30"
                        >
                            {t('catalog_next')}
                        </button>
                    </div>
                )}
            </div>

            {/* Brand story band */}
            <section className="bg-ink text-parchment">
                <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-10 px-6 py-16 lg:grid-cols-[1fr_auto] lg:gap-16">
                    <div>
                        <p className="mb-3 text-xs tracking-[0.3em] text-brass-light uppercase">{t('catalog_story_eyebrow')}</p>
                        <h2 className="font-serif text-3xl">{t('catalog_story_title')}</h2>
                        <p className="mt-4 max-w-xl text-parchment/80">{t('catalog_story_body')}</p>
                    </div>
                    <div className="mx-auto h-32 w-32 shrink-0">
                        <DesignArt motif="hebrew-script" label={t('catalog_story_motif_label')} tone="dark" />
                    </div>
                </div>
            </section>
        </div>
    );
}
