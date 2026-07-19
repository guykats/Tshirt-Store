import { useTranslation } from 'react-i18next';

/**
 * Renders 1-5 stars. Either a static display (role="img" summarizing the
 * value for assistive tech, since a row of glyphs means nothing on its own)
 * or, with `onChange`, an interactive picker built from real buttons so it's
 * keyboard operable and each star has its own accessible name — a bare row
 * of clickable spans is not screen-reader friendly.
 */
export default function StarRating({ value = 0, onChange, size = 'md' }) {
    const { t } = useTranslation();
    const interactive = typeof onChange === 'function';
    const textSize = size === 'lg' ? 'text-2xl' : 'text-base';

    if (!interactive) {
        return (
            <span
                role="img"
                aria-label={t('reviews_stars_summary', { rating: value })}
                className={`${textSize} tracking-tight text-brass`}
            >
                {[1, 2, 3, 4, 5].map((n) => (
                    <span key={n} aria-hidden="true">
                        {n <= Math.round(value) ? '★' : '☆'}
                    </span>
                ))}
            </span>
        );
    }

    return (
        <fieldset className="border-0 p-0">
            <legend className="mb-1 text-sm font-medium">{t('reviews_rating_label')}</legend>
            <div className={`flex gap-1 ${textSize}`}>
                {[1, 2, 3, 4, 5].map((n) => (
                    <button
                        key={n}
                        type="button"
                        aria-pressed={n === value}
                        aria-label={t('reviews_star_aria', { count: n })}
                        onClick={() => onChange(n)}
                        className={`leading-none ${n <= value ? 'text-brass' : 'text-line'} hover:text-brass`}
                    >
                        <span aria-hidden="true">{n <= value ? '★' : '☆'}</span>
                    </button>
                ))}
            </div>
        </fieldset>
    );
}
