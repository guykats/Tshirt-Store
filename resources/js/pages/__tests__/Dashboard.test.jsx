import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import Dashboard from '../Dashboard';
import i18n from '../../i18n';

// OrderController::index paginates 20-at-a-time. The 21st order only exists on
// page 2 — before the fix, Dashboard.jsx's loadFulfillmentOrders fetched page 1
// only and this order (needing fulfillment) silently never appeared.
const PAGE_ONE_ORDERS = {
    data: Array.from({ length: 20 }, (_, i) => ({
        id: i + 1,
        order_number: `ORD-${i + 1}`,
        status: 'delivered',
        payment_status: 'refunded',
        total_amount: 10,
        currency: 'USD',
    })),
    meta: { current_page: 1, last_page: 2, total: 21, per_page: 20 },
};

const PAGE_TWO_ORDERS = {
    data: [
        {
            id: 21,
            order_number: 'ORD-21',
            status: 'approved',
            payment_status: 'paid',
            total_amount: 42,
            currency: 'USD',
        },
    ],
    meta: { current_page: 2, last_page: 2, total: 21, per_page: 20 },
};

const EMPTY_PAGE = { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 20 } };

const getMock = vi.fn((url, config) => {
    if (url === '/api/orders') {
        const page = config?.params?.page ?? 1;
        if (config?.params?.status === 'pending_approval') return Promise.resolve({ data: EMPTY_PAGE });
        return Promise.resolve({ data: page === 2 ? PAGE_TWO_ORDERS : PAGE_ONE_ORDERS });
    }
    if (url === '/api/designs') return Promise.resolve({ data: EMPTY_PAGE });
    if (url === '/api/agent-statuses') return Promise.resolve({ data: { data: [] } });
    if (url === '/api/system-events') return Promise.resolve({ data: { data: [] } });
    if (url === '/api/activity') return Promise.resolve({ data: { data: [] } });
    if (url === '/api/project-tasks') return Promise.resolve({ data: { counts: { todo: 0, in_progress: 0, blocked: 0, done: 0 } } });
    if (url === '/api/inventory/low-stock') return Promise.resolve({ data: { data: [] } });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        post: vi.fn(() => Promise.resolve({ data: {} })),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderDashboard() {
    return render(
        <MemoryRouter initialEntries={['/dashboard']}>
            <Dashboard />
        </MemoryRouter>,
    );
}

describe('Dashboard admin queues', () => {
    beforeEach(async () => {
        getMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('surfaces a fulfillable order that only exists on page 2 of /api/orders', async () => {
        renderDashboard();

        // Present: the 21st order, only reachable via page 2 — it appears twice,
        // once in the fulfillment queue and once in the refund queue.
        const occurrences = await screen.findAllByText('ORD-21');
        expect(occurrences.length).toBe(2);

        // It also appears as refundable (payment_status: paid).
        const refundButtons = await screen.findAllByRole('button', { name: 'Refund' });
        expect(refundButtons.length).toBeGreaterThan(0);

        // Both pages of /api/orders (no status filter) were actually requested.
        const fulfillmentCalls = getMock.mock.calls.filter(
            ([url, config]) => url === '/api/orders' && !config?.params?.status,
        );
        expect(fulfillmentCalls.some(([, config]) => config?.params?.page === 2)).toBe(true);
    });

    it('does not show delivered/refunded page-1 orders in the fulfillment or refund queues', async () => {
        renderDashboard();

        expect((await screen.findAllByText('ORD-21')).length).toBeGreaterThan(0);
        expect(screen.queryByText('ORD-1')).not.toBeInTheDocument();
    });
});
