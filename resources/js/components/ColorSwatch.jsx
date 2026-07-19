// A small, static dictionary of common apparel color names to hex values, used to
// render a decorative circular swatch next to a color's text label wherever a variant
// color is offered as a selectable option (ProductDetail's color pills, the admin
// ProductManagement variant table). Colors are a free-text field an admin can set to
// anything (see AdminProductVariantController) — any name missing from this dictionary
// simply renders no swatch, falling back to the plain text pill exactly as before, so a
// typo or unusual name never breaks the page.
//
// Keys are normalized (lowercased, letters only) so "Heather Grey", "heather-grey" and
// "heathergrey" all resolve to the same entry.
const SWATCH_HEX = {
    black: '#0a0a0a',
    white: '#f6f3ea',
    ivory: '#f0e9d8',
    cream: '#efe4cd',
    sand: '#d9c3a0',
    stone: '#b7a98f',
    tan: '#c8a374',
    khaki: '#b7a97a',
    beige: '#d8c6a3',
    grey: '#8d8c86',
    gray: '#8d8c86',
    heathergrey: '#9b9a92',
    heathergray: '#9b9a92',
    charcoal: '#3a3a38',
    navy: '#1f2a44',
    blue: '#2f4d7a',
    skyblue: '#7fa8c9',
    teal: '#2a6b68',
    forest: '#2f4d33',
    green: '#3f6b41',
    olive: '#6b6f3a',
    sage: '#9caf88',
    burgundy: '#5e1f2e',
    maroon: '#5e1f2e',
    wine: '#5a1f2c',
    red: '#8a2f2f',
    rust: '#8a4a2e',
    brown: '#5b4636',
    mustard: '#c8971f',
    gold: '#b8912f',
    plum: '#5a3652',
    purple: '#5a3d6b',
    pink: '#d4a5b5',
    orange: '#c1652c',
};

export function normalizeColorKey(name) {
    return String(name ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z]/g, '');
}

/**
 * Decorative circular color swatch. Renders nothing for any color name not in the
 * static dictionary above, so unrecognized/arbitrary admin-entered colors gracefully
 * fall back to a text-only pill exactly like before this component existed.
 *
 * Always `aria-hidden`: the swatch is a visual aid next to the color's own text label,
 * never the only way to tell colors apart, so it must never carry the accessible name
 * itself (that stays the adjacent visible text).
 */
export default function ColorSwatch({ color, className = '' }) {
    const hex = SWATCH_HEX[normalizeColorKey(color)];
    if (!hex) return null;

    return (
        <span
            aria-hidden="true"
            className={`inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-ink/15 ${className}`}
            style={{ backgroundColor: hex }}
        />
    );
}
