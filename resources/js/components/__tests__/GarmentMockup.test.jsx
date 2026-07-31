import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
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

// GarmentMockup used to feed every `motif` value straight into DesignArt, whose fixed
// REGISTRY lookup falls back to the Star-of-David SVG for anything it doesn't recognize
// — including a real photograph URL an admin typed into the (already-working)
// product_images / design mockup_url form. It should now render a real <img> for
// anything that looks like a URL or path, while every known motif keyword keeps
// rendering the existing SVG art exactly as before.
describe('GarmentMockup photo vs. motif rendering', () => {
    it('renders an <img> with the given src and alt_text-style label for an https:// photo URL', () => {
        render(
            <GarmentMockup
                motif="https://cdn.example.com/tee-front.jpg"
                product={{ name: 'Line Art Tee' }}
                label="Front of the Line Art Tee"
            />,
        );
        const img = screen.getByRole('img', { name: 'Front of the Line Art Tee' });
        expect(img.tagName).toBe('IMG');
        expect(img).toHaveAttribute('src', 'https://cdn.example.com/tee-front.jpg');
    });

    it('renders an <img> for an http:// photo URL too', () => {
        render(<GarmentMockup motif="http://cdn.example.com/tee-front.jpg" label="Tee" />);
        expect(screen.getByRole('img', { name: 'Tee' })).toHaveAttribute(
            'src',
            'http://cdn.example.com/tee-front.jpg',
        );
    });

    it('renders an <img> for a site-relative path', () => {
        render(<GarmentMockup motif="/storage/products/tee-front.jpg" label="Tee" />);
        expect(screen.getByRole('img', { name: 'Tee' })).toHaveAttribute(
            'src',
            '/storage/products/tee-front.jpg',
        );
    });

    it('falls back to the product name for alt text when no label is given', () => {
        render(<GarmentMockup motif="https://cdn.example.com/tee-front.jpg" product={{ name: 'Line Art Tee' }} />);
        expect(screen.getByRole('img', { name: 'Line Art Tee' })).toBeInTheDocument();
    });

    it('still renders the existing SVG DesignArt output, not an <img>, for a known motif keyword', () => {
        const { container } = render(<GarmentMockup motif="menorah" label="Menorah tee" />);
        expect(container.querySelector('img')).not.toBeInTheDocument();
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it('falls back to the Star-of-David SVG (not a broken <img>) for an unrecognized non-URL string', () => {
        const { container } = render(<GarmentMockup motif="not-a-real-motif" label="Mystery tee" />);
        expect(container.querySelector('img')).not.toBeInTheDocument();
        // Star-of-David is drawn as two overlapping triangles (two <polygon>s inside the
        // DesignArt <svg>), unique among the REGISTRY entries.
        expect(container.querySelectorAll('svg polygon').length).toBe(2);
    });
});
