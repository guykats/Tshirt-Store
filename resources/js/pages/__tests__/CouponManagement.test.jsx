import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import CouponManagement from '../CouponManagement';
import i18n from '../../i18n';

const PAGE_ONE = {
    data: [
        {
            id: 1,
            code: 'SAVE10',
            type: 'percent',
            value: 10,
            expires_at: null,
            max_redemptions: null,
            redemptions_count: 3,
            active: true,
        },
        {
            id: 2,
            code: 'OLDCODE',
            type: 'fixed',
            value: 5,
            expires_at: '2026-01-01T00:00:00Z',
            max_redemptions: 100,
            redemptions_count: 100,
            active: false,
        },
    ],
    meta: { current_page: 1, last_page: 1, total: 2 },
};

const getMock = vi.fn((url) => {
    if (url === '/api/admin/coupons') return Promise.resolve({ data: PAGE_ONE });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

const postMock = vi.fn(() => Promise.resolve({ data: { data: { id: 3, code: 'NEWCODE', type: 'percent', value: 15, active: true } } }));
const putMock = vi.fn(() => Promise.resolve({ data: { data: { id: 1, code: 'SAVE10', type: 'percent', value: 10, active: false } } }));

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        post: (...args) => postMock(...args),
        put: (...args) => putMock(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderPage() {
    return render(
        <MemoryRouter initialEntries={['/dashboard/coupons']}>
            <CouponManagement />
        </MemoryRouter>,
    );
}

describe('CouponManagement page', () => {
    beforeEach(async () => {
        getMock.mockClear();
        postMock.mockClear();
        putMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('lists coupons with their type, value, and status', async () => {
        renderPage();

        expect(await screen.findByText('SAVE10')).toBeInTheDocument();
        expect(screen.getByText('OLDCODE')).toBeInTheDocument();
        expect(screen.getByText('10%')).toBeInTheDocument();
        expect(screen.getByText('$5')).toBeInTheDocument();
        expect(screen.getByText('Active')).toBeInTheDocument();
        expect(screen.getByText('Inactive')).toBeInTheDocument();
    });

    it('creates a new coupon through the accessible form', async () => {
        const user = userEvent.setup();
        renderPage();

        await screen.findByText('SAVE10');

        await user.click(screen.getByRole('button', { name: 'Add coupon' }));

        const codeInput = screen.getByLabelText('Code');
        await user.type(codeInput, 'newcode');
        const valueInput = screen.getByLabelText('Percent off (%)');
        await user.type(valueInput, '15');

        await user.click(screen.getByRole('button', { name: 'Save' }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith('/api/admin/coupons', expect.objectContaining({
                code: 'NEWCODE',
                type: 'percent',
                value: 15,
            }));
        });
    });

    it('deactivates an active coupon with one click', async () => {
        const user = userEvent.setup();
        renderPage();

        await screen.findByText('SAVE10');

        await user.click(screen.getByRole('button', { name: 'Deactivate coupon SAVE10' }));

        await waitFor(() => {
            expect(putMock).toHaveBeenCalledWith('/api/admin/coupons/1', expect.objectContaining({ active: false }));
        });
    });
});
