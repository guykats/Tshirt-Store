import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const SECTIONS = [
    { title: 'privacy_s1_title', body: ['privacy_s1_p1', 'privacy_s1_p2'] },
    { title: 'privacy_s2_title', body: ['privacy_s2_p1'] },
    { title: 'privacy_s3_title', body: ['privacy_s3_p1'] },
    { title: 'privacy_s4_title', body: ['privacy_s4_p1'] },
    { title: 'privacy_s5_title', body: ['privacy_s5_p1'] },
    { title: 'privacy_s6_title', body: ['privacy_s6_p1'] },
    { title: 'privacy_s7_title', body: ['privacy_s7_p1'] },
];

export default function Privacy() {
    const { t } = useTranslation();

    useDocumentMeta(
        t('meta_privacy_title', { app: t('app_name') }),
        t('meta_privacy_description', { app: t('app_name') })
    );

    return (
        <div className="mx-auto max-w-2xl px-6 py-16">
            <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('privacy_eyebrow')}</p>
            <h1 className="mb-2 font-serif text-3xl">{t('privacy_title')}</h1>
            <p className="mb-8 text-xs text-ink-soft">{t('privacy_effective_date')}</p>

            <div className="mb-10 flex justify-center">
                <DesignArt motif="hamsa" className="h-32 w-32 rounded" label={t('privacy_art_label')} />
            </div>

            <div className="space-y-10 leading-relaxed text-ink-soft">
                <p>{t('privacy_intro', { app: t('app_name') })}</p>

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
