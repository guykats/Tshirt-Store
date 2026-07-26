import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import Login from '../Login';
import { AuthProvider } from '../../lib/AuthContext';
import i18n from '../../i18n';

// AuthController::login already honors a `remember` boolean
// (Auth::attempt($credentials, $request->boolean('remember'))) — these tests
// guard against Login.jsx silently dropping that capability by never
// rendering a checkbox for it or never sending the flag it captures.
const loginPost = vi.fn((url) => {
    if (url === '/api/login') return Promise.resolve({ data: {} });
    return Promise.reject(new Error(`unmocked POST ${url}`));
});

const getMock = vi.fn((url) => {
    if (url === '/api/me') return Promise.resolve({ data: { data: { id: 1, name: 'Dana Cohen' } } });
    return Promise.reject(new Error(`unmocked GET ${url}`));
});

vi.mock('../../lib/api', () => ({
    default: {
        get: (...args) => getMock(...args),
        post: (...args) => loginPost(...args),
    },
    ensureCsrfCookie: vi.fn(() => Promise.resolve()),
}));

function renderLogin() {
    return render(
        <MemoryRouter initialEntries={['/login']}>
            <AuthProvider>
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/dashboard" element={<div>Dashboard</div>} />
                </Routes>
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('Login "remember me" checkbox', () => {
    beforeEach(async () => {
        loginPost.mockClear();
        getMock.mockClear();
        await i18n.changeLanguage('en');
    });

    it('renders a properly labeled remember-me checkbox, unchecked by default', async () => {
        renderLogin();

        const checkbox = await screen.findByLabelText('Remember me');
        expect(checkbox).toBeInTheDocument();
        expect(checkbox).toHaveAttribute('type', 'checkbox');
        expect(checkbox.id).toBe('login-remember');
        expect(checkbox).not.toBeChecked();
    });

    it('sends remember: false by default when submitting without checking it', async () => {
        const user = userEvent.setup();
        renderLogin();

        await user.type(screen.getByLabelText('Email'), 'shopper@example.com');
        await user.type(screen.getByLabelText('Password'), 'correct-horse');
        await user.click(screen.getByRole('button', { name: 'Log in' }));

        await screen.findByText('Dashboard');

        expect(loginPost).toHaveBeenCalledWith('/api/login', {
            email: 'shopper@example.com',
            password: 'correct-horse',
            remember: false,
        });
    });

    it('sends remember: true when the checkbox is checked before submitting', async () => {
        const user = userEvent.setup();
        renderLogin();

        await user.type(screen.getByLabelText('Email'), 'shopper@example.com');
        await user.type(screen.getByLabelText('Password'), 'correct-horse');
        await user.click(screen.getByLabelText('Remember me'));
        await user.click(screen.getByRole('button', { name: 'Log in' }));

        await screen.findByText('Dashboard');

        expect(loginPost).toHaveBeenCalledWith('/api/login', {
            email: 'shopper@example.com',
            password: 'correct-horse',
            remember: true,
        });
    });
});
