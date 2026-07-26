import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import Catalog from '../Catalog';
import { AuthProvider } from '../../lib/AuthContext';
import { WishlistProvider } from '../../lib/WishlistContext';
import { SiteSettingsProvider } from '../../lib/SiteSettingsContext';
import i18n from '../../i18n';

const PRODUCTS_PAGE = {
    data: [
        {
            id: 1,
            name: 'Line Art Tee',
            slug: 'line-art-tee',
            base_price: 29.99,
            currency: 'USD',
            design: { mockup_url: 'star-of-david' },
            variants: [{ id: 201, size: 'S', color: 'Black', stock_quantity: 5 }],
        },
    ],
    meta: { current_page: 1, last_page: 1 },
};

// When set, the product-list fetch rejects instead of resolving — used by the
// error-state tests below. Reset to false in each describe block that doesn't
// care about it, so failures in one test can't leak into the next.
let failProductsFetch = false;

vi.mock('../../lib/api', () => ({
    default: {
        get: vi.fn((url) => {
            if (url === '/api/me') {
                return Promise.reject({ response: { status: 401 } });
            }
            if (url === '/api/site-settings') {
                return Promise.resolve({ data: { data: null } });
            }
            if (url === '/api/wishlist') {
                return Promise.resolve({ data: { data: [] } });
            }
            if (url === '/api/home-stats') {
                return Promise.reject(new Error('not under test here'));
            }
            if (url === '/api/testimonials') {
                return Promise.resolve({ data: { data: [] } });
            }
            if (url === '/api/products') {
                if (failProductsFetch) {
                    return Promise.reject({ response: { status: 500 } });
                }
                return Promise.resolve({ data: PRODUCTS_PAGE });
            }
            return Promise.reject(new Error(`unmocked GET ${url}`));
        }),
        post: vi.fn(() => Promise.resolve({ data: {} })),
        delete: vi.fn(() => Promise.resolve({ data: {} })),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderCatalog() {
    return render(
        <MemoryRouter initialEntries={['/']}>
            <AuthProvider>
                <SiteSettingsProvider>
                    <WishlistProvider>
                        <Catalog />
                    </WishlistProvider>
                </SiteSettingsProvider>
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('Catalog product list', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
        failProductsFetch = false;
    });

    it('renders the fetched products once loading finishes', async () => {
        renderCatalog();

        expect(await screen.findByRole('heading', { name: 'Line Art Tee' })).toBeInTheDocument();
    });
});

describe('Catalog failed fetch', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
        failProductsFetch = true;
    });

    afterEach(() => {
        failProductsFetch = false;
    });

    it('renders a distinct accessible error state with a retry option instead of implying the catalog is empty', async () => {
        renderCatalog();

        const alert = await screen.findByRole('alert');
        expect(alert).toHaveTextContent(/couldn't load the collection/i);

        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();

        // The genuinely-empty-catalog copy must not appear for a failed fetch.
        expect(screen.queryByText(/collection is still being cut/i)).not.toBeInTheDocument();
    });

    it('retries the fetch and renders the products once it succeeds', async () => {
        const user = userEvent.setup();
        renderCatalog();

        await screen.findByRole('alert');

        failProductsFetch = false;
        await user.click(screen.getByRole('button', { name: /try again/i }));

        expect(await screen.findByRole('heading', { name: 'Line Art Tee' })).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });
});
