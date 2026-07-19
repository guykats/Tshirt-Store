import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import ProductDetail from '../ProductDetail';
import { AuthProvider } from '../../lib/AuthContext';
import { WishlistProvider } from '../../lib/WishlistContext';
import i18n from '../../i18n';

// A product with three product_images rows (App\Models\ProductImage) — the case the
// thumbnail strip / carousel (ProductGallery.jsx) exists for, replacing the single
// un-clickable GarmentMockup that used to render only Design::mockup_url.
const PRODUCT = {
    id: 1,
    name: 'Line Art Tee',
    slug: 'line-art-tee',
    description: 'A tee.',
    base_price: 29.99,
    currency: 'USD',
    sku: 'TEE-1',
    design: { mockup_url: 'star-of-david' },
    variants: [
        { id: 201, size: 'M', color: 'Black', stock_quantity: 5 },
    ],
    images: [
        { id: 11, url: 'star-of-david', alt_text: 'Front of the Line Art Tee', color: null, position: 0 },
        { id: 12, url: 'menorah', alt_text: 'Back of the Line Art Tee', color: null, position: 1 },
        { id: 13, url: 'hamsa', alt_text: 'Detail of the Line Art Tee', color: null, position: 2 },
    ],
};

vi.mock('../../lib/api', () => ({
    default: {
        get: vi.fn((url) => {
            if (url === '/api/me') {
                return Promise.reject({ response: { status: 401 } });
            }
            if (url.endsWith('/reviews/eligibility')) {
                return Promise.resolve({ data: { data: null } });
            }
            if (url.endsWith('/reviews')) {
                return Promise.resolve({ data: { data: [], meta: { average_rating: null, count: 0 } } });
            }
            if (url === `/api/products/${PRODUCT.slug}`) {
                return Promise.resolve({ data: { data: PRODUCT } });
            }
            return Promise.reject(new Error(`unmocked GET ${url}`));
        }),
        post: vi.fn(() => Promise.resolve({ data: {} })),
        delete: vi.fn(() => Promise.resolve({ data: {} })),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderProductDetail() {
    return render(
        <MemoryRouter initialEntries={[`/products/${PRODUCT.slug}`]}>
            <AuthProvider>
                <WishlistProvider>
                    <Routes>
                        <Route path="/products/:slug" element={<ProductDetail />} />
                    </Routes>
                </WishlistProvider>
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('ProductDetail image gallery', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('renders a thumbnail for every gallery image and updates the main image on click', async () => {
        const user = userEvent.setup();
        renderProductDetail();

        expect(await screen.findByRole('heading', { name: 'Line Art Tee' })).toBeInTheDocument();

        // All three images render as thumbnails, plus one large main image.
        expect(await screen.findByRole('img', { name: 'Front of the Line Art Tee' })).toBeInTheDocument();
        const frontThumb = screen.getByRole('button', { name: 'Front of the Line Art Tee' });
        const backThumb = screen.getByRole('button', { name: 'Back of the Line Art Tee' });
        const detailThumb = screen.getByRole('button', { name: 'Detail of the Line Art Tee' });
        expect(frontThumb).toBeInTheDocument();
        expect(backThumb).toBeInTheDocument();
        expect(detailThumb).toBeInTheDocument();

        // The first image is selected by default.
        expect(frontThumb).toHaveAttribute('aria-pressed', 'true');
        expect(backThumb).toHaveAttribute('aria-pressed', 'false');

        // Clicking a different thumbnail swaps the main image and the pressed state.
        await user.click(backThumb);

        await waitFor(() => {
            expect(screen.getByRole('img', { name: 'Back of the Line Art Tee' })).toBeInTheDocument();
        });
        expect(screen.queryByRole('img', { name: 'Front of the Line Art Tee' })).not.toBeInTheDocument();
        expect(backThumb).toHaveAttribute('aria-pressed', 'true');
        expect(frontThumb).toHaveAttribute('aria-pressed', 'false');

        // The thumbnails themselves are keyboard-operable native buttons — clicking
        // (rather than a synthetic pointer-only handler) already proves this, but this
        // also asserts they aren't e.g. divs with a fake onClick.
        expect(detailThumb.tagName).toBe('BUTTON');
    });
});
