import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import DesignArt from '../DesignArt';

// Products 8-14 (see DatabaseSeeder.php's $catalog and the matching
// database/migrations/2026_08_07_181000_seed_catalog_depth_new_products.php) reuse the
// same cheap HebrewMark-typography approach as chai/shalom/aleph — each new REGISTRY
// entry must render its own real word, not silently fall back to the default
// Star-of-David art (which is what an unrecognized motif key renders instead).
const NEW_HEBREW_MOTIFS = {
    emunah: 'אמונה', // faith
    bracha: 'ברכה', // blessing
    tikvah: 'תקווה', // hope
    ahava: 'אהבה', // love
    simcha: 'שמחה', // joy
    emet: 'אמת', // truth
    or: 'אור', // light
};

describe('DesignArt catalog-depth motifs', () => {
    Object.entries(NEW_HEBREW_MOTIFS).forEach(([motif, text]) => {
        it(`renders its own "${text}" mark for motif="${motif}" instead of falling back to the default`, () => {
            const { container } = render(<DesignArt motif={motif} />);

            const textEl = container.querySelector('svg text');
            expect(textEl).not.toBeNull();
            expect(textEl.textContent).toBe(text);

            // Star-of-David (the REGISTRY fallback for an unrecognized key) draws two
            // <polygon>s; no HebrewMark-based motif ever does.
            expect(container.querySelectorAll('svg polygon').length).toBe(0);
        });
    });

    it('still falls back to the Star-of-David SVG for a genuinely unknown motif key', () => {
        const { container } = render(<DesignArt motif="not-a-real-motif" />);
        expect(container.querySelectorAll('svg polygon').length).toBe(2);
    });

    it('gives every new motif a real aria-label when used as the primary product image', () => {
        const { container } = render(<DesignArt motif="tikvah" label="Tikvah Script Hoodie" />);
        const wrapper = container.firstChild;
        expect(wrapper).toHaveAttribute('role', 'img');
        expect(wrapper).toHaveAttribute('aria-label', 'Tikvah Script Hoodie');
    });
});
