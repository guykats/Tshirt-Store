import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import AdminReviews from '../AdminReviews';
import i18n from '../../i18n';

const PAGE_ONE = {
    data: [
        {
            id: 1,
            rating: 5,
            body: 'Great shirt.',
            reviewer_name: 'Alice',
            product_name: 'Star of David Tee',
            product_slug: 'star-of-david-tee',
            created_at: '2026-07-01T10:00:00Z',
        },
    ],
    meta: { current_page: 1, last_page: 1, total: 1 },
};

const getMock = vi.fn((url) => {
    if (url === '/api/admin/reviews') return Promise.resolve({ data: PAGE_ONE });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

const deleteMock = vi.fn(() => Promise.resolve({ data: { message: 'Deleted.' } }));

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        delete: (...args) => deleteMock(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderAdminReviews() {
    return render(
        <MemoryRouter initialEntries={['/dashboard/reviews']}>
            <AdminReviews />
        </MemoryRouter>,
    );
}

describe('AdminReviews page', () => {
    beforeEach(async () => {
        getMock.mockClear();
        deleteMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('renders reviews across products with an accessible delete action', async () => {
        renderAdminReviews();

        expect(await screen.findByText('Star of David Tee')).toBeInTheDocument();
        expect(screen.getByText('Alice')).toBeInTheDocument();
        expect(screen.getByText('Great shirt.')).toBeInTheDocument();

        expect(
            screen.getByRole('button', { name: 'Delete review by Alice for Star of David Tee' }),
        ).toBeInTheDocument();
    });

    it('deletes a review after confirming, then reloads the list', async () => {
        const user = userEvent.setup();
        renderAdminReviews();

        await screen.findByText('Star of David Tee');

        await user.click(screen.getByRole('button', { name: 'Delete review by Alice for Star of David Tee' }));
        await user.click(screen.getByRole('button', { name: 'Confirm delete' }));

        await waitFor(() => {
            expect(deleteMock).toHaveBeenCalledWith('/api/products/star-of-david-tee/reviews/1');
        });
        await waitFor(() => {
            expect(getMock).toHaveBeenCalledTimes(2);
        });
    });

    it('cancelling the confirmation does not delete the review', async () => {
        const user = userEvent.setup();
        renderAdminReviews();

        await screen.findByText('Star of David Tee');

        await user.click(screen.getByRole('button', { name: 'Delete review by Alice for Star of David Tee' }));
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(deleteMock).not.toHaveBeenCalled();
        expect(screen.getByRole('button', { name: 'Delete review by Alice for Star of David Tee' })).toBeInTheDocument();
    });
});
