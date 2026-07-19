/**
 * Format a monetary amount using the locale-correct grouping, decimal
 * punctuation, and currency symbol placement for the given locale — instead
 * of the app's previous hand-concatenated `{currency} {amount.toFixed(2)}`
 * strings, which always rendered "USD 19.99"-style regardless of locale.
 *
 * Built on the platform's native Intl.NumberFormat, so a Hebrew-locale
 * shopper sees e.g. "19.99 $" (RTL currency-after-amount ordering with the
 * Hebrew-standard bidi marks) rather than the English-ordered string.
 *
 * @param {number|string} amount - The numeric amount, e.g. 19.99.
 * @param {string} currency - ISO 4217 currency code, e.g. 'USD', 'ILS'.
 * @param {string} [locale] - BCP 47 locale tag, e.g. 'en' or 'he'. Falls
 *   back to 'en' if omitted or not a valid locale Intl recognizes.
 * @returns {string} The formatted, locale-aware currency string.
 */
export function formatPrice(amount, currency, locale) {
    const numericAmount = Number(amount);
    const resolvedCurrency = currency || 'USD';
    const resolvedLocale = locale || 'en';

    if (Number.isNaN(numericAmount)) {
        return '';
    }

    try {
        return new Intl.NumberFormat(resolvedLocale, {
            style: 'currency',
            currency: resolvedCurrency,
        }).format(numericAmount);
    } catch {
        // Unknown/invalid currency code or locale — fall back to a plain
        // en-US format rather than throwing and breaking the page.
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(numericAmount);
    }
}

export default formatPrice;
