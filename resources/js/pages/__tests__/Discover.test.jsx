import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import Discover from '../Discover';
import i18n from '../../i18n';

const BATCH = {
    data: [
        { id: 1, batch_date: '2026-07-28', motif: 'chai', style: null, status: 'pending', promoted_design_id: null, created_at: '2026-07-28T03:00:00Z' },
        { id: 2, batch_date: '2026-07-28', motif: 'hamsa', style: null, status: 'pending', promoted_design_id: null, created_at: '2026-07-28T03:00:00Z' },
        { id: 3, batch_date: '2026-07-28', motif: 'menorah', style: null, status: 'pending', promoted_design_id: null, created_at: '2026-07-28T03:00:00Z' },
    ],
    meta: { current_page: 1, last_page: 1, total: 3, per_page: 20 },
};

const NEW_BATCH = {
    data: [
        { id: 4, batch_date: '2026-07-29', motif: 'pomegranate', style: null, status: 'pending', promoted_design_id: null, created_at: '2026-07-29T03:00:00Z' },
    ],
};

const getMock = vi.fn((url) => {
    if (url === '/api/design-suggestions') return Promise.resolve({ data: BATCH });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

const postMock = vi.fn((url) => {
    if (url === '/api/design-suggestions/generate') return Promise.resolve({ data: NEW_BATCH });
    if (url === '/api/design-suggestions/1/keep') return Promise.resolve({ data: { data: { id: 10 } } });
    if (url === '/api/design-suggestions/2/discard') return Promise.resolve({ data: { data: { id: 2, status: 'discarded' } } });
    return Promise.reject(new Error(`unmocked POST ${url}`));
});

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        post: (...args) => postMock(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderDiscover() {
    return render(
        <MemoryRouter initialEntries={['/dashboard/discover']}>
            <Discover />
        </MemoryRouter>,
    );
}

describe('Discover page', () => {
    beforeEach(async () => {
        getMock.mockClear();
        postMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('renders the latest batch as cards', async () => {
        renderDiscover();

        expect(await screen.findAllByRole('button', { name: 'Keep' })).toHaveLength(3);
        expect(screen.getAllByRole('button', { name: 'Discard' })).toHaveLength(3);
        expect(screen.getByText('Chai')).toBeInTheDocument();
        expect(screen.getByText('Hamsa')).toBeInTheDocument();
        expect(screen.getByText('Menorah')).toBeInTheDocument();
    });

    it('keeping a suggestion calls the keep endpoint and removes that card', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const chaiCard = screen.getByText('Chai').closest('div');
        await user.click(within(chaiCard).getByRole('button', { name: 'Keep' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/1/keep');
        });
        await waitFor(() => {
            expect(screen.queryByText('Chai')).not.toBeInTheDocument();
        });
        expect(screen.getByText('Hamsa')).toBeInTheDocument();
    });

    it('discarding a suggestion calls the discard endpoint and removes that card', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const hamsaCard = screen.getByText('Hamsa').closest('div');
        await user.click(within(hamsaCard).getByRole('button', { name: 'Discard' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/2/discard');
        });
        await waitFor(() => {
            expect(screen.queryByText('Hamsa')).not.toBeInTheDocument();
        });
        expect(screen.getByText('Chai')).toBeInTheDocument();
    });

    it('generate now replaces the grid with the returned batch', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        await user.click(screen.getByRole('button', { name: 'Generate now' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/generate');
        });
        await waitFor(() => {
            expect(screen.queryByText('Chai')).not.toBeInTheDocument();
        });
        expect(await screen.findByText('Pomegranate')).toBeInTheDocument();
        expect(screen.getAllByRole('button', { name: 'Keep' })).toHaveLength(1);
    });
});
