import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import About from '../About';
import i18n from '../../i18n';

function renderAbout() {
    return render(
        <MemoryRouter>
            <About />
        </MemoryRouter>,
    );
}

describe('About page hero art', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('renders the Star of David DesignArt as an accessible image with a real label', () => {
        renderAbout();

        const art = screen.getByRole('img', { name: 'Line-art Star of David, the symbol at the heart of this collection' });
        expect(art).toBeInTheDocument();
        expect(art).not.toHaveAttribute('aria-hidden');
    });

    it('renders the label in Hebrew when the locale is switched', async () => {
        await i18n.changeLanguage('he');
        renderAbout();

        const art = screen.getByRole('img', { name: 'איור קווי של מגן דוד, הסמל שבלב האוסף הזה' });
        expect(art).toBeInTheDocument();
    });
});
