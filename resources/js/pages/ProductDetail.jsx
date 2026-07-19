import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useParams } from 'react-router-dom';
import api from '../lib/api';
import ColorSwatch from '../components/ColorSwatch';
import GarmentMockup from '../components/GarmentMockup';
import ProductReviews from '../components/ProductReviews';
import WishlistButton from '../components/WishlistButton';
import { ProductDetailSkeleton } from '../components/Skeleton';
import useDocumentMeta from '../hooks/useDocumentMeta';
import useJsonLd from '../hooks/useJsonLd';

export default function ProductDetail() {
    const { t } = useTranslation();
    const { slug } = useParams();
    const [product, setProduct] = useState(null);
    const [size, setSize] = useState('');
    const [color, setColor] = useState('');

    useDocumentMeta(
        product ? `${product.name} — ${t('app_name')}` : t('app_name'),
        product?.description,
    );

    const productJsonLd = useMemo(() => {
        if (!product) return null;

        const pageUrl = window.location.href;
        // No real per-product photography exists yet (product visuals are inline SVG
        // line art, see DesignArt.jsx) — reuse the same brand-consistent image already
        // shipped for Open Graph rather than fabricating a per-product image URL.
        const imageUrl = `${window.location.origin}/og-image.png`;
        const variants = product.variants || [];
        const anyInStock = variants.some((v) => v.stock_quantity > 0);

        return {
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: product.name,
            description: product.description,
            sku: product.sku,
            image: [imageUrl],
            url: pageUrl,
            brand: {
                '@type': 'Brand',
                name: t('app_name'),
            },
            // A single Offer, not AggregateOffer: every size/color variant of a given
            // product shares one price in this catalog (that's also what the page itself
            // renders — one price, not a range), and Google's own guidance is that
            // AggregateOffer is for the same product sold by multiple sellers, not for
            // describing a set of size/color variants.
            offers: {
                '@type': 'Offer',
                priceCurrency: product.currency,
                price: Number(product.base_price).toFixed(2),
                availability: anyInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                itemCondition: 'https://schema.org/NewCondition',
                url: pageUrl,
            },
        };
        // Structured data has no aggregateRating field: the Product model/API has no
        // review or rating data to draw from, and fabricating one would violate
        // Google's structured-data guidelines. Add it here if/when a Review model ships.
    }, [product, t]);

    useJsonLd(productJsonLd);

    useEffect(() => {
        api.get(`/api/products/${slug}`).then((res) => {
            setProduct(res.data.data);
            const first = res.data.data.variants.find((v) => v.stock_quantity > 0) || res.data.data.variants[0];
            if (first) {
                setSize(first.size);
                setColor(first.color);
            }
        });
    }, [slug]);

    if (!product) {
        return (
            <div className="mx-auto max-w-4xl px-6 py-12">
                <ProductDetailSkeleton />
            </div>
        );
    }

    const sizes = [...new Set(product.variants.map((v) => v.size))];
    const colors = [...new Set(product.variants.map((v) => v.color))];
    const selected = product.variants.find((v) => v.size === size && v.color === color);

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <Link to="/" className="mb-8 inline-block text-sm text-ink-soft hover:text-ink">
                &larr; {t('catalog_title')}
            </Link>

            <div className="grid grid-cols-1 gap-10 md:grid-cols-2">
                <div className="relative">
                    <GarmentMockup
                        motif={product.design?.mockup_url}
                        product={product}
                        color={color}
                        className="aspect-square rounded"
                        label={t('product_mockup_label', { name: product.name, color })}
                    />
                    <WishlistButton product={product} className="absolute top-3 right-3" />
                </div>

                <div>
                    <h1 className="font-serif text-3xl">{product.name}</h1>
                    <p className="mt-2 text-lg text-ink-soft">
                        {product.currency} {product.base_price.toFixed(2)}
                    </p>
                    <p className="mt-6 leading-relaxed text-ink-soft">{product.description}</p>

                    <div className="mt-8">
                        <p className="mb-2 text-sm font-medium">{t('checkout_variant_color')}</p>
                        <div className="flex gap-2">
                            {colors.map((c) => (
                                <button
                                    key={c}
                                    onClick={() => setColor(c)}
                                    className={`inline-flex items-center gap-1.5 rounded-full border px-4 py-1.5 text-sm ${
                                        color === c ? 'border-ink bg-ink text-parchment' : 'border-line text-ink-soft'
                                    }`}
                                >
                                    <ColorSwatch color={c} />
                                    {c}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="mt-4">
                        <div className="mb-2 flex items-center justify-between gap-4">
                            <p className="text-sm font-medium">{t('checkout_variant_size')}</p>
                            <Link to="/size-guide" className="text-xs text-brass hover:underline">
                                {t('product_size_guide_link')}
                            </Link>
                        </div>
                        <div className="flex gap-2">
                            {sizes.map((s) => {
                                const inStock = product.variants.some((v) => v.size === s && v.color === color && v.stock_quantity > 0);
                                return (
                                    <button
                                        key={s}
                                        disabled={!inStock}
                                        onClick={() => setSize(s)}
                                        className={`h-10 w-10 rounded-full border text-sm disabled:cursor-not-allowed disabled:opacity-30 ${
                                            size === s ? 'border-ink bg-ink text-parchment' : 'border-line text-ink-soft'
                                        }`}
                                    >
                                        {s}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <Link
                        to={selected ? `/checkout/${product.slug}?variant=${selected.id}` : '#'}
                        className={`mt-10 inline-block w-full rounded px-6 py-3 text-center text-sm tracking-wide uppercase ${
                            selected ? 'bg-ink text-parchment hover:bg-ink-soft' : 'pointer-events-none bg-line text-ink-soft'
                        }`}
                    >
                        {selected && selected.stock_quantity === 0 ? t('checkout_out_of_stock') : t('buy_now')}
                    </Link>
                </div>
            </div>

            <ProductReviews productSlug={product.slug} />
        </div>
    );
}
