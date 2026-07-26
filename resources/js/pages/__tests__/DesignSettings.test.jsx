import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DesignSettings from '../DesignSettings';
import i18n from '../../i18n';

const SITE_SETTINGS = {
    logo_path: '',
    accent_color: '#8c6a3f',
    hero_tagline_en: 'Wear your heritage',
    hero_tagline_he: 'לבשו את המורשת שלכם',
    hero_subheading_en: 'Everyday apparel rooted in Jewish identity.',
    hero_subheading_he: 'בגדי יומיום שמושרשים בזהות יהודית.',
    hero_motif: 'star-of-david',
};

const getMock = vi.fn((url) => {
    if (url === '/api/site-settings') return Promise.resolve({ data: { data: SITE_SETTINGS } });
    if (url === '/api/testimonials/manage') return Promise.resolve({ data: { data: [] } });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        patch: vi.fn(() => Promise.resolve({ data: { data: SITE_SETTINGS } })),
        post: vi.fn(),
        delete: vi.fn(),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

describe('DesignSettings page', () => {
    beforeEach(async () => {
        getMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('gives the live hero-motif preview an accessible name matching the selected option', async () => {
        render(<DesignSettings />);

        await screen.findByLabelText('Hero Motif');

        const preview = screen.getByRole('img', { name: 'Star of David' });
        expect(preview).toBeInTheDocument();
    });

    it('updates the preview accessible name when the dropdown selection changes', async () => {
        const user = userEvent.setup();
        render(<DesignSettings />);

        const select = await screen.findByLabelText('Hero Motif');
        await user.selectOptions(select, 'menorah');

        await waitFor(() => {
            expect(screen.getByRole('img', { name: 'Menorah' })).toBeInTheDocument();
        });
        expect(screen.queryByRole('img', { name: 'Star of David' })).not.toBeInTheDocument();
    });
});
