import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import Layout from '../Layout';
import { AuthProvider } from '../lib/AuthContext';
import { SiteSettingsProvider } from '../lib/SiteSettingsContext';
import i18n from '../i18n';

// The nav/footer chrome around toggleLocale reads user/settings from these two
// contexts. Neither is under test here, so every api.js call is rejected —
// both providers fall back to their logged-out/no-settings state, which is
// all Layout needs to render.
vi.mock('../lib/api', () => ({
    default: {
        get: vi.fn(() => Promise.reject(new Error('not mocked in this test'))),
        post: vi.fn(() => Promise.resolve({ data: {} })),
        delete: vi.fn(() => Promise.resolve({ data: {} })),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderLayout() {
    return render(
        <MemoryRouter>
            <AuthProvider>
                <SiteSettingsProvider>
                    <Layout>
                        <div>page content</div>
                    </Layout>
                </SiteSettingsProvider>
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('Layout toggleLocale', () => {
    beforeEach(async () => {
        localStorage.clear();
        await i18n.changeLanguage('en');
        document.documentElement.dir = 'ltr';
        document.documentElement.lang = 'en';
    });

    it('swaps language, localStorage, and document.dir/lang from en to he', async () => {
        const user = userEvent.setup();
        renderLayout();

        const toggle = screen.getByRole('button', { name: 'עברית' });
        await user.click(toggle);

        await waitFor(() => expect(i18n.language).toBe('he'));
        expect(localStorage.getItem('locale')).toBe('he');
        expect(document.documentElement.dir).toBe('rtl');
        expect(document.documentElement.lang).toBe('he');

        // The button's own label flips too, proving the component re-rendered
        // off the new i18n state rather than just mutating globals.
        await waitFor(() => expect(screen.getByRole('button', { name: 'English' })).toBeInTheDocument());
    });

    it('swaps back from he to en on a second toggle', async () => {
        const user = userEvent.setup();
        renderLayout();

        await user.click(screen.getByRole('button', { name: 'עברית' }));
        await waitFor(() => expect(i18n.language).toBe('he'));

        await user.click(screen.getByRole('button', { name: 'English' }));

        await waitFor(() => expect(i18n.language).toBe('en'));
        expect(localStorage.getItem('locale')).toBe('en');
        expect(document.documentElement.dir).toBe('ltr');
        expect(document.documentElement.lang).toBe('en');
    });
});
