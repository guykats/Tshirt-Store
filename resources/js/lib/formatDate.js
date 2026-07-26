/**
 * Format a date/timestamp using locale-correct ordering and punctuation for
 * the given locale — instead of the app's previous parameterless
 * `toLocaleDateString()` / `toLocaleString()` calls, which always rendered
 * in the browser's default (effectively US English) style regardless of
 * the active site language.
 *
 * Built on the platform's native Intl.DateTimeFormat, mirroring how
 * formatPrice.js wraps Intl.NumberFormat.
 *
 * @param {string|number|Date} dateInput - Anything the Date constructor accepts.
 * @param {string} [locale] - BCP 47 locale tag, e.g. 'en' or 'he'. Falls
 *   back to 'en' if omitted or not a valid locale Intl recognizes.
 * @param {object} [options] - Intl.DateTimeFormat options. Defaults to a
 *   plain date (no time-of-day).
 * @returns {string} The formatted, locale-aware date string.
 */
export function formatDate(dateInput, locale, options) {
    const date = new Date(dateInput);
    const resolvedLocale = locale || 'en';
    const resolvedOptions = options || { dateStyle: 'short' };

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat(resolvedLocale, resolvedOptions).format(date);
    } catch {
        return new Intl.DateTimeFormat('en-US', resolvedOptions).format(date);
    }
}

/**
 * Same as formatDate, but includes time-of-day (mirrors toLocaleString()).
 */
export function formatDateTime(dateInput, locale) {
    return formatDate(dateInput, locale, { dateStyle: 'short', timeStyle: 'short' });
}

export default formatDate;
