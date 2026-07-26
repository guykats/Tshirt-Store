import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import GarmentMockup from '../GarmentMockup';

// GarmentMockup used to resolve its fabric palette via an exact, case-sensitive lookup
// against a 2-entry { Black, Sand } dictionary, so any other variant color (including
// "black" lowercase) silently rendered the beige Sand fabric. It should now derive the
// fabric fill from ColorSwatch's shared SWATCH_HEX dictionary for any recognized color.
function fabricFill(container) {
    const stop = container.querySelector('linearGradient stop[offset="55%"]');
    return stop.getAttribute('stop-color');
}

describe('GarmentMockup color resolution', () => {
    it('renders the hand-tuned Black palette for an exact "Black" match', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="Black" />);
        expect(fabricFill(container)).toBe('#201c17');
    });

    it('renders the hand-tuned Sand palette for an exact "Sand" match', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="Sand" />);
        expect(fabricFill(container)).toBe('#e7dac2');
    });

    it('is case-insensitive for the hand-tuned Black palette', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="black" />);
        expect(fabricFill(container)).toBe('#201c17');
    });

    it('derives a navy fabric fill for "Navy" instead of falling back to Sand', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="Navy" />);
        const fill = fabricFill(container);
        expect(fill).not.toBe('#e7dac2');
        expect(fill.toLowerCase()).toBe('#1f2a44');
    });

    it('derives a charcoal fabric fill for "Charcoal" instead of falling back to Sand', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="Charcoal" />);
        const fill = fabricFill(container);
        expect(fill).not.toBe('#e7dac2');
        expect(fill.toLowerCase()).toBe('#3a3a38');
    });

    it('falls back to the Sand palette for a totally unrecognized color name', () => {
        const { container } = render(<GarmentMockup motif="star-of-david" color="Ultraviolet Plaid" />);
        expect(fabricFill(container)).toBe('#e7dac2');
    });
});
