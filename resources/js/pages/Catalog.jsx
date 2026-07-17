import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function Catalog() {
    const { t } = useTranslation();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);

    useDocumentMeta(t('app_name'), t('meta_catalog_description'));

    useEffect(() => {
        api.get('/api/products')
            .then((res) => setProducts(res.data.data))
            .finally(() => setLoading(false));
    }, []);

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
            </div>
        </div>
    );
}
