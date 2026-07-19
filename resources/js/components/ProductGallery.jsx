import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import GarmentMockup from './GarmentMockup';

// Renders a product's gallery of `product_images` rows (see App\Models\ProductImage)
// as a large main visual plus a thumbnail strip, replacing what used to be a single,
// un-clickable <GarmentMockup> fed only by the linked Design's motif. Every "image" in
// this catalog — gallery images included — is really a DesignArt motif keyword composited
// onto a GarmentMockup silhouette (see GarmentMockup.jsx's own comment on why there's no
// real product photography yet), not a photograph, so each thumbnail is a small
// GarmentMockup of its own rather than an <img>.
export default function ProductGallery({ product, images, color, className = '' }) {
    const { t } = useTranslation();

    // Images can optionally be scoped to one variant color (ProductImage.color). Prefer
    // the ones that match the shopper's current color selection, but fall back to the
    // full set if that color has no images of its own, and fall back further to the
    // product's own Design motif if there are no gallery images at all yet.
    const visibleImages = useMemo(() => {
        const all = images || [];
        if (all.length === 0) return [];
        const matchingColor = all.filter((img) => !img.color || img.color === color);
        return matchingColor.length > 0 ? matchingColor : all;
    }, [images, color]);

    const fallbackMotif = product?.design?.mockup_url;
    const hasGalleryImages = visibleImages.length > 0;

    const [selectedId, setSelectedId] = useState(visibleImages[0]?.id ?? null);

    useEffect(() => {
        if (!hasGalleryImages) return;
        if (!visibleImages.some((img) => img.id === selectedId)) {
            setSelectedId(visibleImages[0].id);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [visibleImages]);

    const selectedImage = hasGalleryImages
        ? visibleImages.find((img) => img.id === selectedId) || visibleImages[0]
        : null;

    const mainMotif = selectedImage ? selectedImage.url : fallbackMotif;
    const mainLabel = selectedImage
        ? selectedImage.alt_text || t('product_gallery_alt_fallback', { name: product?.name, index: visibleImages.indexOf(selectedImage) + 1 })
        : t('product_mockup_label', { name: product?.name, color });

    return (
        <div className={className}>
            <GarmentMockup
                motif={mainMotif}
                product={product}
                color={color}
                className="aspect-square rounded"
                label={mainLabel}
            />

            {visibleImages.length > 1 && (
                <div
                    role="group"
                    aria-label={t('product_gallery_thumbnails_label')}
                    className="mt-3 flex gap-2 overflow-x-auto"
                >
                    {visibleImages.map((image, index) => {
                        const isSelected = selectedImage && image.id === selectedImage.id;
                        const label = image.alt_text || t('product_gallery_alt_fallback', { name: product?.name, index: index + 1 });

                        return (
                            <button
                                key={image.id}
                                type="button"
                                onClick={() => setSelectedId(image.id)}
                                aria-label={label}
                                aria-pressed={isSelected}
                                className={`h-16 w-16 shrink-0 overflow-hidden rounded border ${
                                    isSelected ? 'border-ink' : 'border-line'
                                }`}
                            >
                                <GarmentMockup motif={image.url} product={product} color={color} className="h-full w-full" />
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
