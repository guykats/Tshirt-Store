import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import Faq from '../Faq';
import i18n from '../../i18n';

function renderFaq() {
    return render(
        <MemoryRouter>
            <Faq />
        </MemoryRouter>,
    );
}

describe('Faq accordion', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('expands a question to reveal its answer, then collapses it again on a second click', () => {
        renderFaq();

        const question = screen.getByRole('button', { name: 'Do you have a size chart?' });
        expect(question).toHaveAttribute('aria-expanded', 'false');

        const panelId = question.getAttribute('aria-controls');
        const panel = document.getElementById(panelId);
        expect(panel).toHaveAttribute('hidden');

        fireEvent.click(question);

        expect(question).toHaveAttribute('aria-expanded', 'true');
        expect(panel).not.toHaveAttribute('hidden');
        expect(panel).toHaveTextContent(/full chart in centimeters/i);

        fireEvent.click(question);

        expect(question).toHaveAttribute('aria-expanded', 'false');
        expect(panel).toHaveAttribute('hidden');
    });

    it('renders a working size-guide link inside the sizing category once expanded', () => {
        renderFaq();

        const question = screen.getByRole('button', { name: 'Do you have a size chart?' });
        fireEvent.click(question);

        const link = screen.getByRole('link', { name: 'View our full size guide →' });
        expect(link).toHaveAttribute('href', '/size-guide');
    });

    it('does not render a size-guide link for a question outside the sizing category', () => {
        renderFaq();

        const question = screen.getByRole('button', { name: 'What size should I order?' });
        fireEvent.click(question);

        expect(screen.queryByRole('link', { name: 'View our full size guide →' })).not.toBeInTheDocument();
    });
});
