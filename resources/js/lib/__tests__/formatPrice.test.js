import { describe, expect, it } from 'vitest';
import { formatPrice } from '../formatPrice';

describe('formatPrice', () => {
    it('formats a USD amount in en with locale-correct grouping and decimals', () => {
        expect(formatPrice(1234.5, 'USD', 'en')).toBe('$1,234.50');
    });

    it('formats a plain USD amount in en', () => {
        expect(formatPrice(19.99, 'USD', 'en')).toBe('$19.99');
    });

    it('formats a USD amount in he using Hebrew/RTL currency ordering', () => {
        // Hebrew locale currency formatting places the symbol after the
        // number (with bidi control characters), unlike en's "$19.99".
        const result = formatPrice(19.99, 'USD', 'he');
        expect(result).toContain('19.99');
        expect(result).toContain('$');
        expect(result.indexOf('19.99')).toBeLessThan(result.indexOf('$'));
    });

    it('formats an ILS amount in he with the shekel symbol', () => {
        const result = formatPrice(1234.5, 'ILS', 'he');
        expect(result).toContain('1,234.50');
        expect(result).toContain('₪'); // ₪
    });

    it('defaults to en/USD when locale or currency is omitted', () => {
        expect(formatPrice(5, undefined, undefined)).toBe('$5.00');
    });

    it('accepts a numeric string amount, matching the toFixed(2) call sites it replaces', () => {
        expect(formatPrice('19.99', 'USD', 'en')).toBe('$19.99');
    });

    it('falls back to en-US formatting for an unrecognized currency code instead of throwing', () => {
        expect(() => formatPrice(10, 'NOTREAL', 'en')).not.toThrow();
    });

    it('returns an empty string for a non-numeric amount instead of "NaN"', () => {
        expect(formatPrice('not-a-number', 'USD', 'en')).toBe('');
    });
});
