import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Catalog() {
    const { t } = useTranslation();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
    const [searchParams, setSearchParams] = useSearchParams();
    const page = Number(searchParams.get('page')) || 1;

    useDocumentMeta(t('app_name'), t('meta_catalog_description'));

    useEffect(() => {
        setLoading(true);
        api.get('/api/products', { params: { page } })
            .then((res) => {
                setProducts(res.data.data);
                setMeta(res.data.meta);
            })
            .finally(() => setLoading(false));
        window.scrollTo({ top: 0 });
    }, [page]);

    function goToPage(nextPage) {
        setSearchParams(nextPage === 1 ? {} : { page: String(nextPage) });
    }

    return (
        <div>
            <div className="border-b border-line px-6 py-16 text-center">
                <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('hero_eyebrow')}</p>
                <h1 className="font-serif text-4xl">{t('hero_title')}</h1>
                <p className="mx-auto mt-4 max-w-md text-ink-soft">{t('hero_subtitle')}</p>
            </div>

            <div className="mx-auto max-w-6xl px-6 py-14">
                {loading && <p className="text-ink-soft">…</p>}

                {!loading && products.length === 0 && <p className="text-ink-soft">{t('catalog_empty')}</p>}

                <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((product) => (
                        <Link key={product.id} to={`/products/${product.slug}`} className="group block">
                            <DesignArt motif={product.design?.mockup_url} className="aspect-square rounded transition-colors group-hover:bg-line" />
                            <h2 className="mt-4 font-serif text-lg">{product.name}</h2>
                            <p className="mt-1 text-sm text-ink-soft">
                                {product.currency} {product.base_price.toFixed(2)}
                            </p>
                        </Link>
                    ))}
                </div>

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
        </div>
    );
}
