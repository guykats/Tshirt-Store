import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function NotFound() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_not_found_title', { app: t('app_name') }), t('not_found_message'));

    return (
        <div className="mx-auto max-w-md px-6 py-20 text-center">
            <div className="mb-8 flex justify-center">
                <DesignArt motif="star-of-david" className="h-32 w-32 rounded" label={t('not_found_art_label')} />
            </div>

            <div role="alert">
                <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('not_found_eyebrow')}</p>
                <h1 className="mb-4 font-serif text-3xl">{t('not_found_title')}</h1>
                <p className="mb-8 leading-relaxed text-ink-soft">{t('not_found_message')}</p>
            </div>

            <Link
                to="/"
                className="inline-block rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft"
            >
                {t('not_found_back_home')}
            </Link>
        </div>
    );
}
