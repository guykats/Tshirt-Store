import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import EmptyState from '../EmptyState';
import i18n from '../../i18n';

describe('EmptyState', () => {
    beforeEach(async () => {
        await i18n.changeLanguage('en');
    });

    it('renders the motif with an accessible name, a heading, and body text', () => {
        render(
            <EmptyState
                motif="hamsa"
                motifLabel="Line-art hamsa"
                title="Nothing saved yet"
                body="Tap the heart on a product to save it here."
            />,
        );

        // The motif is the primary visual for this block (not decorative next
        // to already-visible text), so it should announce via role="img" +
        // aria-label — the same contract DesignArt uses everywhere else.
        expect(screen.getByRole('img', { name: 'Line-art hamsa' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Nothing saved yet' })).toBeInTheDocument();
        expect(screen.getByText('Tap the heart on a product to save it here.')).toBeInTheDocument();
    });

    it('omits the body paragraph and action when none is given', () => {
        render(<EmptyState motif="star-of-david" motifLabel="Line-art Star of David" title="Nothing here" />);

        expect(screen.getByRole('heading', { name: 'Nothing here' })).toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});
