import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';

export default function Catalog() {
    const { t } = useTranslation();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        api.get('/api/products')
            .then((res) => setProducts(res.data.data))
            .finally(() => setLoading(false));
    }, []);

    return (
        <div className="mx-auto max-w-5xl px-6 py-10">
            <h1 className="mb-6 text-2xl font-semibold">{t('catalog_title')}</h1>

            {loading && <p className="text-neutral-500">…</p>}

            {!loading && products.length === 0 && (
                <p className="text-neutral-500">{t('catalog_empty')}</p>
            )}

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                {products.map((product) => (
                    <div key={product.id} className="rounded-lg border border-neutral-200 p-4">
                        <h2 className="font-medium">{product.name}</h2>
                        <p className="text-sm text-neutral-500">{product.description}</p>
                        <p className="mt-2 font-semibold">
                            {product.currency} {product.base_price.toFixed(2)}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
