/**
 * The fixed set of countries offered on address forms, plus locale-correct
 * display names for them — built on the platform's native
 * Intl.DisplayNames, mirroring formatPrice.js's pattern of using Intl
 * directly instead of hand-maintained translation tables, so Hebrew-locale
 * shoppers see real Hebrew country names (e.g. "ישראל") rather than a
 * transliteration someone typed into i18n/index.js by hand.
 *
 * US and IL are listed first since this bilingual EN/Hebrew store's two
 * primary markets; the rest are sorted alphabetically by their localized
 * label so the order still makes sense to a Hebrew-locale shopper.
 */

const PRIORITY_COUNTRY_CODES = ['US', 'IL'];
const OTHER_COUNTRY_CODES = ['CA', 'GB', 'AU', 'FR', 'DE', 'NL', 'BE', 'CH', 'IT', 'ES', 'SE', 'MX', 'BR', 'ZA'];

function countryDisplayNames(locale) {
    try {
        return new Intl.DisplayNames([locale], { type: 'region' });
    } catch {
        return null;
    }
}

/**
 * @param {string} [locale] - BCP 47 locale tag, e.g. 'en' or 'he'. Falls
 *   back to 'en' if omitted or not a valid locale Intl recognizes.
 * @returns {{code: string, label: string}[]}
 */
export function shippingCountryOptions(locale) {
    const resolvedLocale = locale === 'he' ? 'he' : 'en';
    const displayNames = countryDisplayNames(resolvedLocale);
    const label = (code) => {
        if (!displayNames) return code;
        try {
            return displayNames.of(code) || code;
        } catch {
            return code;
        }
    };

    const priority = PRIORITY_COUNTRY_CODES.map((code) => ({ code, label: label(code) }));
    const rest = OTHER_COUNTRY_CODES.map((code) => ({ code, label: label(code) })).sort((a, b) =>
        a.label.localeCompare(b.label, resolvedLocale),
    );

    return [...priority, ...rest];
}

export default shippingCountryOptions;
