import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const SECTIONS = [
    { title: 'terms_s1_title', body: ['terms_s1_p1'] },
    { title: 'terms_s2_title', body: ['terms_s2_p1'] },
    { title: 'terms_s3_title', body: ['terms_s3_p1'] },
    { title: 'terms_s4_title', body: ['terms_s4_p1', 'terms_s4_p2'] },
    { title: 'terms_s5_title', body: ['terms_s5_p1'] },
    { title: 'terms_s6_title', body: ['terms_s6_p1'] },
    { title: 'terms_s7_title', body: ['terms_s7_p1'] },
    { title: 'terms_s8_title', body: ['terms_s8_p1'] },
    { title: 'terms_s9_title', body: ['terms_s9_p1'] },
];

export default function Terms() {
    const { t } = useTranslation();

    useDocumentMeta(
        t('meta_terms_title', { app: t('app_name') }),
        t('meta_terms_description', { app: t('app_name') })
    );

    return (
        <div className="mx-auto max-w-2xl px-6 py-16">
            <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('terms_eyebrow')}</p>
            <h1 className="mb-2 font-serif text-3xl">{t('terms_title')}</h1>
            <p className="mb-8 text-xs text-ink-soft">{t('terms_effective_date')}</p>

            <div className="mb-10 flex justify-center">
                <DesignArt motif="olive-branch" className="h-32 w-32 rounded" label={t('terms_art_label')} />
            </div>

            <div className="space-y-10 leading-relaxed text-ink-soft">
                <p>{t('terms_intro', { app: t('app_name') })}</p>

                {SECTIONS.map((section) => (
                    <section key={section.title}>
                        <h2 className="mb-2 font-serif text-xl text-ink">{t(section.title)}</h2>
                        <div className="space-y-3">
                            {section.body.map((key) => (
                                <p key={key}>{t(key)}</p>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}
