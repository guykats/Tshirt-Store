import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import AuditLog from '../AuditLog';
import i18n from '../../i18n';

const PAGE_ONE = {
    data: [
        { id: 1, event_type: 'order.paid', description: 'Order ABC-1 paid.', actor_type: 'user', actor_name: 'Alice', metadata: null, created_at: '2026-07-01T10:00:00Z' },
    ],
    meta: { current_page: 1, last_page: 2, total: 2, per_page: 30 },
};

const PAGE_TWO = {
    data: [
        { id: 2, event_type: 'design.approved', description: 'Design "Chai" approved.', actor_type: 'user', actor_name: 'Bob', metadata: null, created_at: '2026-07-02T10:00:00Z' },
    ],
    meta: { current_page: 2, last_page: 2, total: 2, per_page: 30 },
};

const getMock = vi.fn((url, config) => {
    if (url === '/api/system-events') {
        const page = config?.params?.page;
        if (page === 2) return Promise.resolve({ data: PAGE_TWO });
        if (config?.params?.event_type === 'design.approved') return Promise.resolve({ data: PAGE_TWO });
        return Promise.resolve({ data: PAGE_ONE });
    }
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderAuditLog() {
    return render(
        <MemoryRouter initialEntries={['/dashboard/audit-log']}>
            <AuditLog />
        </MemoryRouter>,
    );
}

describe('AuditLog page', () => {
    beforeEach(async () => {
        getMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('renders paginated events with filters and can page forward', async () => {
        const user = userEvent.setup();
        renderAuditLog();

        expect(await screen.findByText('Order ABC-1 paid.')).toBeInTheDocument();
        expect(screen.getByText('Page 1 of 2')).toBeInTheDocument();

        // Filter dropdowns/inputs are labeled and keyboard-operable.
        expect(screen.getByLabelText('Event Type')).toBeInTheDocument();
        expect(screen.getByLabelText('Actor Type')).toBeInTheDocument();
        expect(screen.getByLabelText('From Date')).toBeInTheDocument();
        expect(screen.getByLabelText('To Date')).toBeInTheDocument();

        const nextButton = screen.getByRole('button', { name: 'Next' });
        expect(nextButton).toBeEnabled();
        await user.click(nextButton);

        await waitFor(() => {
            expect(screen.getByText('Design "Chai" approved.')).toBeInTheDocument();
        });
        expect(screen.getByText('Page 2 of 2')).toBeInTheDocument();
    });

    it('filters by event type', async () => {
        const user = userEvent.setup();
        renderAuditLog();

        expect(await screen.findByText('Order ABC-1 paid.')).toBeInTheDocument();

        await user.selectOptions(screen.getByLabelText('Event Type'), 'design.approved');

        await waitFor(() => {
            expect(getMock).toHaveBeenCalledWith(
                '/api/system-events',
                expect.objectContaining({ params: expect.objectContaining({ event_type: 'design.approved' }) }),
            );
        });
    });
});
