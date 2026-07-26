import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { CatalogSkeleton, ProductDetailSkeleton } from '../Skeleton';
import i18n from '../../i18n';

// Both skeletons are shown on the two highest-traffic customer pages
// (Catalog, ProductDetail) while product data fetches. They should mirror
// RouteLoading.jsx's role="status" aria-live="polite" contract so screen
// reader users get an announced loading state instead of silence, and an
// announcement again once the region's content changes.
describe('CatalogSkeleton', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('is an announced status region with a visually-hidden loading label', () => {
        render(<CatalogSkeleton />);

        const status = screen.getByRole('status');
        expect(status).toHaveAttribute('aria-live', 'polite');
        expect(screen.getByText('Loading products…')).toBeInTheDocument();
    });

    it('announces the Hebrew label when the locale is Hebrew', async () => {
        await i18n.changeLanguage('he');
        render(<CatalogSkeleton />);

        expect(screen.getByRole('status')).toHaveAttribute('aria-live', 'polite');
        expect(screen.getByText('טוען מוצרים…')).toBeInTheDocument();

        await i18n.changeLanguage('en');
    });
});

describe('ProductDetailSkeleton', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('is an announced status region with a visually-hidden loading label', () => {
        render(<ProductDetailSkeleton />);

        const status = screen.getByRole('status');
        expect(status).toHaveAttribute('aria-live', 'polite');
        expect(screen.getByText('Loading product…')).toBeInTheDocument();
    });

    it('announces the Hebrew label when the locale is Hebrew', async () => {
        await i18n.changeLanguage('he');
        render(<ProductDetailSkeleton />);

        expect(screen.getByRole('status')).toHaveAttribute('aria-live', 'polite');
        expect(screen.getByText('טוען מוצר…')).toBeInTheDocument();

        await i18n.changeLanguage('en');
    });
});
