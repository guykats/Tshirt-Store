import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

// Unisex crew-neck tee chart. "To fit chest" is a body measurement (how the
// garment is designed to fit around the fullest part of the chest), while
// length and sleeve are flat garment measurements — see size_guide_chart_note.
const CHART = [
    { size: 'S', chest: '86–91 cm / 34–36 in', length: '70 cm / 27.5 in', sleeve: '20 cm / 8 in' },
    { size: 'M', chest: '96–101 cm / 38–40 in', length: '72 cm / 28.3 in', sleeve: '21 cm / 8.3 in' },
    { size: 'L', chest: '106–111 cm / 42–44 in', length: '74 cm / 29.1 in', sleeve: '22 cm / 8.7 in' },
    { size: 'XL', chest: '116–121 cm / 46–48 in', length: '76 cm / 29.9 in', sleeve: '23 cm / 9.1 in' },
    { size: 'XXL', chest: '126–131 cm / 50–52 in', length: '78 cm / 30.7 in', sleeve: '24 cm / 9.4 in' },
];

const MEASURE_STEPS = [
    { title: 'size_guide_measure_chest_title', body: 'size_guide_measure_chest_body' },
    { title: 'size_guide_measure_length_title', body: 'size_guide_measure_length_body' },
    { title: 'size_guide_measure_sleeve_title', body: 'size_guide_measure_sleeve_body' },
];

export default function SizeGuide() {
    const { t } = useTranslation();

    useDocumentMeta(
        t('meta_size_guide_title', { app: t('app_name') }),
        t('meta_size_guide_description', { app: t('app_name') })
    );

    return (
        <div className="mx-auto max-w-3xl px-6 py-16">
            <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('size_guide_eyebrow')}</p>
            <h1 className="mb-8 font-serif text-3xl">{t('size_guide_title')}</h1>

            <div className="mb-10 flex justify-center">
                <DesignArt motif="pomegranate" className="h-32 w-32 rounded" label={t('size_guide_art_label')} />
            </div>

            <p className="mb-10 leading-relaxed text-ink-soft">{t('size_guide_intro', { app: t('app_name') })}</p>

            <section className="mb-12">
                <h2 className="mb-2 font-serif text-xl text-ink">{t('size_guide_chart_title')}</h2>
                <p className="mb-4 text-sm leading-relaxed text-ink-soft">{t('size_guide_chart_note')}</p>
                <div className="overflow-x-auto rounded border border-line">
                    <table className="w-full min-w-[520px] border-collapse text-start text-sm">
                        <caption className="sr-only">{t('size_guide_chart_title')}</caption>
                        <thead>
                            <tr className="border-b border-line bg-parchment-dim text-ink">
                                <th scope="col" className="px-4 py-3 text-start font-medium">{t('size_guide_table_size')}</th>
                                <th scope="col" className="px-4 py-3 text-start font-medium">{t('size_guide_table_chest')}</th>
                                <th scope="col" className="px-4 py-3 text-start font-medium">{t('size_guide_table_length')}</th>
                                <th scope="col" className="px-4 py-3 text-start font-medium">{t('size_guide_table_sleeve')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {CHART.map((row, i) => (
                                <tr key={row.size} className={i < CHART.length - 1 ? 'border-b border-line' : ''}>
                                    <th scope="row" className="px-4 py-3 text-start font-medium text-ink">{row.size}</th>
                                    <td className="px-4 py-3 text-ink-soft">{row.chest}</td>
                                    <td className="px-4 py-3 text-ink-soft">{row.length}</td>
                                    <td className="px-4 py-3 text-ink-soft">{row.sleeve}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="mb-12">
                <h2 className="mb-2 font-serif text-xl text-ink">{t('size_guide_fit_title')}</h2>
                <div className="space-y-3 leading-relaxed text-ink-soft">
                    <p>{t('size_guide_fit_p1')}</p>
                    <p>{t('size_guide_fit_p2')}</p>
                </div>
            </section>

            <section>
                <h2 className="mb-2 font-serif text-xl text-ink">{t('size_guide_measure_title')}</h2>
                <p className="mb-6 leading-relaxed text-ink-soft">{t('size_guide_measure_intro')}</p>
                <ol className="space-y-5">
                    {MEASURE_STEPS.map((step, i) => (
                        <li key={step.title} className="flex gap-4">
                            <span
                                aria-hidden="true"
                                className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-brass text-xs text-brass"
                            >
                                {i + 1}
                            </span>
                            <div>
                                <p className="font-medium text-ink">{t(step.title)}</p>
                                <p className="text-sm leading-relaxed text-ink-soft">{t(step.body)}</p>
                            </div>
                        </li>
                    ))}
                </ol>
                <p className="mt-8 text-sm leading-relaxed text-ink-soft">{t('size_guide_measure_tip')}</p>
            </section>
        </div>
    );
}
