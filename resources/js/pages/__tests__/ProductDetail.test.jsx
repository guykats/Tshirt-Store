import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import ProductDetail from '../ProductDetail';
import { AuthProvider } from '../../lib/AuthContext';
import { WishlistProvider } from '../../lib/WishlistContext';
import i18n from '../../i18n';

// Deliberately missing an S/Sand variant altogether, alongside an M/Black
// variant that exists but is sold out, so the suite can exercise both flavors
// of "can't buy this" the selector has to handle: a real combination with
// zero stock, and a combination that was never a variant at all.
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
        { id: 201, size: 'S', color: 'Black', stock_quantity: 5 },
        { id: 202, size: 'M', color: 'Black', stock_quantity: 0 },
        { id: 203, size: 'M', color: 'Sand', stock_quantity: 4 },
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

function buyLink() {
    return screen.getByRole('link', { name: /buy now|out of stock/i });
}

describe('ProductDetail size/color selector', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('auto-selects the first in-stock variant and disables an out-of-stock size for the current color', async () => {
        renderProductDetail();

        expect(await screen.findByRole('heading', { name: 'Line Art Tee' })).toBeInTheDocument();

        // Default selection is S / Black (the first variant with stock > 0).
        const sizeS = screen.getByRole('button', { name: 'S' });
        const sizeM = screen.getByRole('button', { name: 'M' });
        expect(sizeS).not.toBeDisabled();
        // M/Black exists but has zero stock, so it must be disabled.
        expect(sizeM).toBeDisabled();

        const link = buyLink();
        expect(link).toHaveTextContent('Buy Now');
        expect(link).toHaveAttribute('href', expect.stringContaining('variant=201'));
        expect(link.className).not.toMatch(/pointer-events-none/);
    });

    it('disables the buy button once the selected size/color combination no longer exists, and re-enables it once a valid combination is chosen', async () => {
        const user = userEvent.setup();
        renderProductDetail();

        await screen.findByRole('heading', { name: 'Line Art Tee' });

        // Switch color to Sand while size is still S: there is no S/Sand variant
        // at all, so nothing should be considered selected/purchasable.
        await user.click(screen.getByRole('button', { name: 'Sand' }));

        expect(screen.getByRole('button', { name: 'S' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'M' })).not.toBeDisabled();

        const disabledLink = buyLink();
        expect(disabledLink.className).toMatch(/pointer-events-none/);
        expect(disabledLink.getAttribute('href')).not.toMatch(/variant=/);

        // Completing the selection with the in-stock M/Sand variant re-enables it.
        await user.click(screen.getByRole('button', { name: 'M' }));

        const enabledLink = buyLink();
        expect(enabledLink.className).not.toMatch(/pointer-events-none/);
        expect(enabledLink).toHaveAttribute('href', expect.stringContaining('variant=203'));
        expect(enabledLink).toHaveTextContent('Buy Now');
    });
});
