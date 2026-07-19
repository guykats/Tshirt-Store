import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import WishlistButton from '../components/WishlistButton';
import useDocumentMeta from '../hooks/useDocumentMeta';
import { useWishlist } from '../lib/WishlistContext';
import { formatPrice } from '../lib/formatPrice';

export default function Wishlist() {
    const { t, i18n } = useTranslation();
    const { productIds } = useWishlist();
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    useDocumentMeta(t('meta_wishlist_title', { app: t('app_name') }));

    useEffect(() => {
        api.get('/api/wishlist')
            .then((res) => setItems(res.data.data))
            .finally(() => setLoading(false));
    }, []);

    // Filtering by the shared WishlistContext's live product-id set (rather than
    // just rendering the initial fetch) means un-saving an item here removes it
    // from this list immediately, without a second round-trip to the server.
    const visibleItems = items.filter((item) => productIds.has(item.product.id));

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <h1 className="mb-6 font-serif text-2xl">{t('wishlist_title')}</h1>

            {loading && <p className="text-ink-soft">…</p>}
            {!loading && visibleItems.length === 0 && <p className="text-ink-soft">{t('wishlist_empty')}</p>}

            <ul className="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2">
                {visibleItems.map((item) => (
                    <li key={item.id} className="group relative">
                        <Link to={`/products/${item.product.slug}`} className="block">
                            <DesignArt motif={item.product.design?.mockup_url} className="aspect-square rounded transition-colors group-hover:bg-line" />
                            <h2 className="mt-4 font-serif text-lg">{item.product.name}</h2>
                            <p className="mt-1 text-sm text-ink-soft">
                                {formatPrice(item.product.base_price, item.product.currency, i18n.language)}
                            </p>
                        </Link>
                        <WishlistButton product={item.product} className="absolute top-3 right-3" />
                    </li>
                ))}
            </ul>
        </div>
    );
}
