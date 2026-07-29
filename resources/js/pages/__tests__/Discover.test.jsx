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
        { id: 3, batch_date: '2026-07-28', motif: 'menorah', style: null, status: 'kept', promoted_design_id: null, created_at: '2026-07-28T03:00:00Z' },
        { id: 4, batch_date: '2026-07-28', motif: 'pomegranate', style: null, status: 'published', promoted_design_id: 99, created_at: '2026-07-28T03:00:00Z' },
        { id: 5, batch_date: '2026-07-28', motif: 'olive-branch', style: null, status: 'discarded', promoted_design_id: null, created_at: '2026-07-28T03:00:00Z' },
    ],
    meta: { current_page: 1, last_page: 1, total: 5, per_page: 20 },
};

const NEW_BATCH = {
    data: [
        { id: 6, batch_date: '2026-07-29', motif: 'star-of-david', style: null, status: 'pending', promoted_design_id: null, created_at: '2026-07-29T03:00:00Z' },
    ],
};

const getMock = vi.fn((url) => {
    if (url === '/api/design-suggestions') return Promise.resolve({ data: BATCH });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

const postMock = vi.fn((url) => {
    if (url === '/api/design-suggestions/generate') return Promise.resolve({ data: NEW_BATCH });
    if (url === '/api/design-suggestions/1/keep') {
        return Promise.resolve({ data: { data: { id: 1, status: 'kept' } } });
    }
    if (url === '/api/design-suggestions/2/discard') {
        return Promise.resolve({ data: { data: { id: 2, status: 'discarded' } } });
    }
    if (url === '/api/design-suggestions/3/publish') {
        return Promise.resolve({ data: { data: { id: 200, title: 'Menorah Design', status: 'pending_approval' } } });
    }
    if (url === '/api/design-suggestions/publish-all') {
        return Promise.resolve({ data: { data: [{ id: 201, title: 'Menorah Design', status: 'pending_approval' }] } });
    }
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

        expect(await screen.findAllByRole('button', { name: 'Keep' })).toHaveLength(2);
        expect(screen.getAllByRole('button', { name: 'Discard' })).toHaveLength(2);
        expect(screen.getByText('Chai')).toBeInTheDocument();
        expect(screen.getByText('Hamsa')).toBeInTheDocument();
        expect(screen.getByText('Menorah')).toBeInTheDocument();
        expect(screen.getByText('Pomegranate')).toBeInTheDocument();
        expect(screen.getByText('Olive Branch')).toBeInTheDocument();
    });

    it('stat bar shows a count per status and filters the grid on click', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const pendingTile = screen.getByRole('button', { name: /2\s+Pending/ });
        const keptTile = screen.getByRole('button', { name: /1\s+Kept/ });
        const publishedTile = screen.getByRole('button', { name: /1\s+Published/ });
        const discardedTile = screen.getByRole('button', { name: /1\s+Discarded/ });
        expect(pendingTile).toBeInTheDocument();
        expect(keptTile).toBeInTheDocument();
        expect(publishedTile).toBeInTheDocument();
        expect(discardedTile).toBeInTheDocument();

        await user.click(keptTile);

        expect(screen.getByText('Menorah')).toBeInTheDocument();
        expect(screen.queryByText('Chai')).not.toBeInTheDocument();
        expect(screen.queryByText('Pomegranate')).not.toBeInTheDocument();

        // clicking the same tile again clears the filter
        await user.click(keptTile);
        expect(screen.getByText('Chai')).toBeInTheDocument();
    });

    it('keeping a suggestion calls the keep endpoint, keeps the card visible, and shows Publish instead of Keep/Discard', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const chaiCard = screen.getByText('Chai').closest('div');
        await user.click(within(chaiCard).getByRole('button', { name: 'Keep' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/1/keep');
        });

        expect(screen.getByText('Chai')).toBeInTheDocument();
        await waitFor(() => {
            const updatedChaiCard = screen.getByText('Chai').closest('div');
            expect(within(updatedChaiCard).getByRole('button', { name: 'Publish' })).toBeInTheDocument();
            expect(within(updatedChaiCard).queryByRole('button', { name: 'Keep' })).not.toBeInTheDocument();
            expect(within(updatedChaiCard).queryByRole('button', { name: 'Discard' })).not.toBeInTheDocument();
        });
    });

    it('discarding a suggestion calls the discard endpoint and keeps the card visible as discarded', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const hamsaCard = screen.getByText('Hamsa').closest('div');
        await user.click(within(hamsaCard).getByRole('button', { name: 'Discard' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/2/discard');
        });

        expect(screen.getByText('Hamsa')).toBeInTheDocument();
        await waitFor(() => {
            const updatedHamsaCard = screen.getByText('Hamsa').closest('div');
            expect(within(updatedHamsaCard).queryByRole('button', { name: 'Keep' })).not.toBeInTheDocument();
            expect(within(updatedHamsaCard).queryByRole('button', { name: 'Discard' })).not.toBeInTheDocument();
        });
    });

    it('clicking Publish on a kept card calls the publish endpoint and moves that card to published', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const menorahCard = screen.getByText('Menorah').closest('div');
        await user.click(within(menorahCard).getByRole('button', { name: 'Publish' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/3/publish');
        });

        await waitFor(() => {
            const updatedMenorahCard = screen.getByText('Menorah').closest('div');
            expect(within(updatedMenorahCard).queryByRole('button', { name: 'Publish' })).not.toBeInTheDocument();
        });
        await waitFor(() => {
            expect(screen.getByRole('status')).toHaveTextContent('published');
        });
    });

    it('"Publish all kept" calls the bulk endpoint and updates all kept cards to published', async () => {
        const user = userEvent.setup();
        renderDiscover();

        await screen.findAllByRole('button', { name: 'Keep' });

        const bulkButton = screen.getByRole('button', { name: /Publish all kept/ });
        expect(bulkButton).toBeEnabled();

        await user.click(bulkButton);

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/design-suggestions/publish-all');
        });

        await waitFor(() => {
            const updatedMenorahCard = screen.getByText('Menorah').closest('div');
            expect(within(updatedMenorahCard).queryByRole('button', { name: 'Publish' })).not.toBeInTheDocument();
        });
        await waitFor(() => {
            expect(screen.getByRole('status')).toHaveTextContent('1 kept suggestion(s) published');
        });
    });

    it('the "Publish all kept" button is disabled when there are no kept suggestions', async () => {
        getMock.mockImplementationOnce((url) => {
            if (url === '/api/design-suggestions') {
                return Promise.resolve({
                    data: {
                        data: BATCH.data.filter((s) => s.status !== 'kept'),
                        meta: { current_page: 1, last_page: 1, total: 4, per_page: 20 },
                    },
                });
            }
            return Promise.reject(new Error(`unmocked GET ${url}`));
        });

        renderDiscover();

        const bulkButton = await screen.findByRole('button', { name: /Publish all kept/ });
        expect(bulkButton).toBeDisabled();
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
        expect(await screen.findByText('Star of David')).toBeInTheDocument();
        expect(screen.getAllByRole('button', { name: 'Keep' })).toHaveLength(1);
    });
});
