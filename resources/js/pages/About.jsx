import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function About() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_about_title', { app: t('app_name') }), t('about_p1'));

    return (
        <div className="mx-auto max-w-2xl px-6 py-16">
            <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('about_eyebrow')}</p>
            <h1 className="mb-8 font-serif text-3xl">{t('about_title')}</h1>

            <div className="mb-10 flex justify-center">
                <DesignArt motif="star-of-david" className="h-40 w-40 rounded" />
            </div>

            <div className="space-y-6 leading-relaxed text-ink-soft">
                <p>{t('about_p1')}</p>
                <p>{t('about_p2')}</p>
                <p>{t('about_p3')}</p>
            </div>
        </div>
    );
}
