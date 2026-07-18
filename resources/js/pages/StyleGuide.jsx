import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const COLOR_TOKENS = [
    { name: '--color-ink', hex: '#17140f' },
    { name: '--color-ink-soft', hex: '#4a453c' },
    { name: '--color-parchment', hex: '#f7f4ee' },
    { name: '--color-parchment-dim', hex: '#efeadf' },
    { name: '--color-brass', hex: '#8c6a3f' },
    { name: '--color-brass-light', hex: '#b79868' },
    { name: '--color-line', hex: '#e4dfd4' },
];

const SPACING_TOKENS = [
    { token: 'px-6', usage: 'Page/section horizontal padding (Catalog, About, Progress, Chat)' },
    { token: 'py-10', usage: 'Page vertical padding on internal/dashboard pages' },
    { token: 'py-14 / py-16', usage: 'Section vertical padding on marketing sections (hero, story)' },
    { token: 'gap-4', usage: 'Compact groups — filter bars, button rows' },
    { token: 'gap-8 / gap-10', usage: 'Grid/flex gaps between cards and columns' },
    { token: 'mb-6 / mt-8', usage: 'Vertical rhythm between a heading and the content that follows it' },
];

const MOTIFS = ['star-of-david', 'menorah', 'chai', 'hamsa', 'hebrew-script', 'pomegranate', 'olive-branch'];

function Swatch({ name, hex }) {
    return (
        <div className="overflow-hidden rounded border border-line">
            <div className="h-16 w-full" style={{ backgroundColor: `var(${name})` }} />
            <div className="px-3 py-2">
                <p className="font-mono text-xs">{name}</p>
                <p className="text-xs text-ink-soft uppercase">{hex}</p>
            </div>
        </div>
    );
}

export default function StyleGuide() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_style_guide_title', { app: t('app_name') }));

    return (
        <div className="mx-auto max-w-6xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('style_guide_title')}</h1>
            <p className="mb-10 max-w-2xl text-sm text-ink-soft">{t('style_guide_hint')}</p>

            {/* Colors */}
            <section className="mb-14">
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_colors_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_colors_hint')}</p>
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    {COLOR_TOKENS.map((c) => (
                        <Swatch key={c.name} name={c.name} hex={c.hex} />
                    ))}
                </div>
            </section>

            {/* Type scale */}
            <section className="mb-14">
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_type_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_type_hint')}</p>
                <div className="space-y-4 rounded border border-line p-6">
                    <div>
                        <p className="font-serif text-4xl leading-tight sm:text-5xl">{t('style_guide_type_sample_hero')}</p>
                        <p className="mt-1 font-mono text-xs text-ink-soft">font-serif text-4xl sm:text-5xl — hero headings (Catalog)</p>
                    </div>
                    <div>
                        <p className="font-serif text-2xl">{t('style_guide_type_sample_section')}</p>
                        <p className="mt-1 font-mono text-xs text-ink-soft">font-serif text-2xl — page/section titles</p>
                    </div>
                    <div>
                        <p className="font-serif text-lg">{t('style_guide_type_sample_card')}</p>
                        <p className="mt-1 font-mono text-xs text-ink-soft">font-serif text-lg — card/product titles</p>
                    </div>
                    <div>
                        <p className="text-base">{t('style_guide_type_sample_body')}</p>
                        <p className="mt-1 font-mono text-xs text-ink-soft">font-sans text-base — body copy</p>
                    </div>
                    <div>
                        <p className="text-sm text-ink-soft">{t('style_guide_type_sample_meta')}</p>
                        <p className="mt-1 font-mono text-xs text-ink-soft">font-sans text-sm text-ink-soft — secondary/meta text</p>
                    </div>
                </div>
            </section>

            {/* Spacing */}
            <section className="mb-14">
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_spacing_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_spacing_hint')}</p>
                <div className="overflow-x-auto rounded border border-line">
                    <table className="w-full text-sm">
                        <thead className="bg-parchment-dim text-left">
                            <tr>
                                <th className="px-4 py-2">{t('style_guide_spacing_col_token')}</th>
                                <th className="px-4 py-2">{t('style_guide_spacing_col_usage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {SPACING_TOKENS.map((s) => (
                                <tr key={s.token} className="border-t border-line">
                                    <td className="px-4 py-2 font-mono text-xs whitespace-nowrap">{s.token}</td>
                                    <td className="px-4 py-2 text-ink-soft">{s.usage}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Buttons */}
            <section className="mb-14">
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_buttons_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_buttons_hint')}</p>
                <div className="flex flex-wrap items-center gap-4 rounded border border-line p-6">
                    <button className="rounded bg-ink px-6 py-3 text-sm text-parchment transition-colors hover:bg-ink/90">
                        {t('style_guide_button_primary')}
                    </button>
                    <button className="rounded border border-ink px-6 py-3 text-sm transition-colors hover:bg-parchment-dim">
                        {t('style_guide_button_secondary')}
                    </button>
                    <button className="rounded bg-green-600 px-3 py-1.5 text-sm text-white">{t('approve')}</button>
                    <button className="rounded bg-red-600 px-3 py-1.5 text-sm text-white">{t('reject')}</button>
                </div>
            </section>

            {/* Form input */}
            <section className="mb-14">
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_form_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_form_hint')}</p>
                <div className="max-w-xs rounded border border-line p-6">
                    <label htmlFor="style-guide-input" className="mb-1 block text-sm text-ink-soft">
                        {t('style_guide_form_label')}
                    </label>
                    <input
                        id="style-guide-input"
                        type="text"
                        placeholder={t('style_guide_form_placeholder')}
                        className="w-full rounded border border-line px-4 py-2 text-sm"
                    />
                </div>
            </section>

            {/* DesignArt motifs */}
            <section>
                <h2 className="mb-1 font-serif text-lg">{t('style_guide_motifs_title')}</h2>
                <p className="mb-4 text-sm text-ink-soft">{t('style_guide_motifs_hint')}</p>

                <p className="mb-2 text-xs text-ink-soft uppercase">{t('style_guide_motifs_light')}</p>
                <div className="mb-6 grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                    {MOTIFS.map((motif) => (
                        <DesignArt key={motif} motif={motif} label={motif} className="aspect-square rounded" />
                    ))}
                </div>

                <p className="mb-2 text-xs text-ink-soft uppercase">{t('style_guide_motifs_dark')}</p>
                <div className="grid grid-cols-3 gap-4 rounded bg-ink p-4 sm:grid-cols-4 lg:grid-cols-7">
                    {MOTIFS.map((motif) => (
                        <DesignArt key={motif} motif={motif} label={motif} tone="dark" className="aspect-square rounded" />
                    ))}
                </div>
            </section>
        </div>
    );
}
