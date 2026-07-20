import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { OrderConfirmation } from '../Checkout';
import i18n from '../../i18n';

const order = {
    id: 42,
    order_number: 'ORD-1042',
    total_amount: 89.5,
    currency: 'USD',
    tracking_url: null,
    items: [
        {
            id: 1,
            quantity: 2,
            product_variant: {
                size: 'M',
                color: 'Ink',
                product: { name: 'Star of David Tee' },
            },
        },
    ],
};

function Harness({ orderData, user }) {
    const { t, i18n: instance } = useTranslation();
    return <OrderConfirmation order={orderData} user={user} t={t} i18n={instance} />;
}

function renderConfirmation(orderData = order, user = null) {
    return render(
        <MemoryRouter>
            <Harness orderData={orderData} user={user} />
        </MemoryRouter>,
    );
}

describe('OrderConfirmation (post-purchase success screen)', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('shows a celebratory motif with a real accessible label, the order number, and an order summary', () => {
        renderConfirmation();

        expect(screen.getByRole('img', { name: /completed purchase/i })).toBeInTheDocument();
        expect(screen.getByText('Order ORD-1042')).toBeInTheDocument();
        expect(screen.getByText(/2 × Star of David Tee \(M \/ Ink\)/)).toBeInTheDocument();
        expect(screen.getByText('$89.50')).toBeInTheDocument();
    });

    it('links back to the catalog to continue shopping', () => {
        renderConfirmation();

        const link = screen.getByRole('link', { name: 'Continue Shopping' });
        expect(link).toHaveAttribute('href', '/');
    });

    it('only renders a tracking link when the order actually has one', () => {
        const { rerender } = renderConfirmation();
        expect(screen.queryByRole('link', { name: 'Track Your Package' })).not.toBeInTheDocument();

        rerender(
            <MemoryRouter>
                <Harness orderData={{ ...order, tracking_url: 'https://www.ups.com/track?loc=en_US&tracknum=1Z999' }} user={null} />
            </MemoryRouter>,
        );
        const trackingLink = screen.getByRole('link', { name: 'Track Your Package' });
        expect(trackingLink).toHaveAttribute('href', 'https://www.ups.com/track?loc=en_US&tracknum=1Z999');
    });

    it('shows a guest-specific order-lookup notice only when there is no logged-in user', () => {
        renderConfirmation(order, null);
        expect(screen.getByText(/Checked out as a guest/)).toBeInTheDocument();

        renderConfirmation(order, { id: 1, name: 'Dana' });
        expect(screen.queryAllByText(/Checked out as a guest/)).toHaveLength(1);
    });
});
