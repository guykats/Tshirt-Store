import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import Checkout, { OrderConfirmation } from '../Checkout';
import { AuthProvider } from '../../lib/AuthContext';
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

const PRODUCT = {
    id: 77,
    name: 'Line Art Tee',
    base_price: 29.99,
    currency: 'USD',
    variants: [{ id: 201, size: 'M', color: 'Black', stock_quantity: 5 }],
};

const checkoutPost = vi.fn(() => Promise.resolve({ data: { order: { id: 1 }, paypal_order_id: 'PAYPAL-1' } }));

vi.mock('../../lib/api', () => ({
    default: {
        get: vi.fn((url) => {
            if (url === '/api/me') return Promise.reject({ response: { status: 401 } });
            if (url === '/api/products/77') return Promise.resolve({ data: { data: PRODUCT } });
            return Promise.reject(new Error(`unmocked GET ${url}`));
        }),
        post: (...args) => checkoutPost(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderCheckoutForm() {
    return render(
        <MemoryRouter initialEntries={['/checkout/77']}>
            <AuthProvider>
                <Routes>
                    <Route path="/checkout/:productId" element={<Checkout />} />
                </Routes>
            </AuthProvider>
        </MemoryRouter>,
    );
}

async function fillRequiredFields(user) {
    await screen.findByLabelText('Email');
    await user.type(screen.getByLabelText('Email'), 'shopper@example.com');
    await user.type(screen.getByLabelText('Full Name'), 'Dana Cohen');
    await user.type(screen.getByLabelText('Address Line 1'), '1 Herzl St');
    await user.type(screen.getByLabelText('City'), 'Tel Aviv');
    await user.type(screen.getByLabelText('State'), 'Tel Aviv');
    await user.type(screen.getByLabelText('Postal Code'), '6100000');
}

describe('Checkout form (address/coupon fields wrapped in a real <form>)', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
        checkoutPost.mockClear();
    });

    it('submits via clicking the Continue to Payment button', async () => {
        renderCheckoutForm();
        const user = userEvent.setup();
        await fillRequiredFields(user);

        await user.click(screen.getByRole('button', { name: 'Continue to Payment' }));

        expect(checkoutPost).toHaveBeenCalledWith('/api/checkout', expect.objectContaining({
            email: 'shopper@example.com',
            product_variant_id: 201,
            shipping_address: expect.objectContaining({ country: 'US' }),
        }));
    });

    it('lets a shopper pick a non-US country, adapts the state field label, and sends the chosen country to the API', async () => {
        renderCheckoutForm();
        const user = userEvent.setup();
        await fillRequiredFields(user);

        await user.selectOptions(screen.getByLabelText('Country'), 'IL');
        expect(screen.getByLabelText('State / Province')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Continue to Payment' }));

        expect(checkoutPost).toHaveBeenCalledWith('/api/checkout', expect.objectContaining({
            shipping_address: expect.objectContaining({ country: 'IL' }),
        }));
    });

    it('also submits when pressing Enter inside a field, since the fields sit inside a real <form>', async () => {
        renderCheckoutForm();
        const user = userEvent.setup();
        await fillRequiredFields(user);

        await user.type(screen.getByLabelText('Postal Code'), '{Enter}');

        expect(checkoutPost).toHaveBeenCalledWith('/api/checkout', expect.objectContaining({
            email: 'shopper@example.com',
            product_variant_id: 201,
        }));
    });
});

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
