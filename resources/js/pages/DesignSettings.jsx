import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const MOTIF_OPTIONS = [
    'star-of-david',
    'menorah',
    'chai',
    'shalom',
    'hamsa',
    'pomegranate',
    'aleph',
    'olive-branch',
    'hebrew-script',
];

const HEX_COLOR_PATTERN = /^#[0-9a-fA-F]{6}$/;

const EMPTY_FORM = {
    logo_path: '',
    accent_color: '#8c6a3f',
    hero_tagline_en: '',
    hero_tagline_he: '',
    hero_subheading_en: '',
    hero_subheading_he: '',
    hero_motif: 'star-of-david',
    stat_pieces_shipped: 0,
    stat_rating: 0,
    stat_countries: 0,
};

export default function DesignSettings() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_design_settings_title', { app: t('app_name') }));

    const [form, setForm] = useState(EMPTY_FORM);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState(null); // 'saved' | 'error' | null

    useEffect(() => {
        api.get('/api/site-settings')
            .then((res) => {
                const data = res.data.data;
                setForm({
                    logo_path: data.logo_path || '',
                    accent_color: data.accent_color,
                    hero_tagline_en: data.hero_tagline_en,
                    hero_tagline_he: data.hero_tagline_he,
                    hero_subheading_en: data.hero_subheading_en,
                    hero_subheading_he: data.hero_subheading_he,
                    hero_motif: data.hero_motif,
                    stat_pieces_shipped: data.stat_pieces_shipped,
                    stat_rating: data.stat_rating,
                    stat_countries: data.stat_countries,
                });
            })
            .finally(() => setLoading(false));
    }, []);

    function updateField(field, value) {
        setForm((prev) => ({ ...prev, [field]: value }));
    }

    const colorIsValid = HEX_COLOR_PATTERN.test(form.accent_color);

    async function handleSubmit(e) {
        e.preventDefault();
        setStatus(null);
        setSaving(true);
        try {
            const res = await api.patch('/api/site-settings', {
                ...form,
                logo_path: form.logo_path || null,
                stat_pieces_shipped: Number(form.stat_pieces_shipped),
                stat_rating: Number(form.stat_rating),
                stat_countries: Number(form.stat_countries),
            });
            const data = res.data.data;
            setForm({
                logo_path: data.logo_path || '',
                accent_color: data.accent_color,
                hero_tagline_en: data.hero_tagline_en,
                hero_tagline_he: data.hero_tagline_he,
                hero_subheading_en: data.hero_subheading_en,
                hero_subheading_he: data.hero_subheading_he,
                hero_motif: data.hero_motif,
                stat_pieces_shipped: data.stat_pieces_shipped,
                stat_rating: data.stat_rating,
                stat_countries: data.stat_countries,
            });
            setStatus('saved');
        } catch {
            setStatus('error');
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return (
            <div className="mx-auto max-w-3xl px-6 py-10">
                <p className="text-ink-soft">…</p>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('design_settings_title')}</h1>
            <p className="mb-8 max-w-2xl text-sm text-ink-soft">{t('design_settings_hint')}</p>

            <form onSubmit={handleSubmit} className="space-y-10">
                <section>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label htmlFor="design-logo" className="mb-1 block text-sm">{t('design_settings_logo_label')}</label>
                            <input
                                id="design-logo"
                                type="text"
                                value={form.logo_path}
                                onChange={(e) => updateField('logo_path', e.target.value)}
                                placeholder="https://…"
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                            <p className="mt-1 text-xs text-ink-soft">{t('design_settings_logo_hint')}</p>
                        </div>
                        <div>
                            <label htmlFor="design-accent-color" className="mb-1 block text-sm">{t('design_settings_accent_color_label')}</label>
                            <div className="flex items-center gap-3">
                                <input
                                    id="design-accent-color"
                                    type="color"
                                    value={colorIsValid ? form.accent_color : '#8c6a3f'}
                                    onChange={(e) => updateField('accent_color', e.target.value)}
                                    className="h-10 w-14 rounded border border-line"
                                />
                                <input
                                    type="text"
                                    aria-label={t('design_settings_accent_color_label')}
                                    value={form.accent_color}
                                    onChange={(e) => updateField('accent_color', e.target.value)}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2 font-mono"
                                />
                            </div>
                            {!colorIsValid && (
                                <p role="alert" className="mt-1 text-xs text-red-700">{t('design_settings_invalid_color')}</p>
                            )}
                        </div>
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 font-serif text-lg">{t('design_settings_hero_section_title')}</h2>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label htmlFor="design-hero-tagline-en" className="mb-1 block text-sm">{t('design_settings_hero_tagline_en_label')}</label>
                            <input
                                id="design-hero-tagline-en"
                                type="text"
                                required
                                value={form.hero_tagline_en}
                                onChange={(e) => updateField('hero_tagline_en', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-hero-tagline-he" className="mb-1 block text-sm">{t('design_settings_hero_tagline_he_label')}</label>
                            <input
                                id="design-hero-tagline-he"
                                type="text"
                                dir="rtl"
                                required
                                value={form.hero_tagline_he}
                                onChange={(e) => updateField('hero_tagline_he', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-hero-subheading-en" className="mb-1 block text-sm">{t('design_settings_hero_subheading_en_label')}</label>
                            <textarea
                                id="design-hero-subheading-en"
                                required
                                rows={3}
                                value={form.hero_subheading_en}
                                onChange={(e) => updateField('hero_subheading_en', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-hero-subheading-he" className="mb-1 block text-sm">{t('design_settings_hero_subheading_he_label')}</label>
                            <textarea
                                id="design-hero-subheading-he"
                                required
                                dir="rtl"
                                rows={3}
                                value={form.hero_subheading_he}
                                onChange={(e) => updateField('hero_subheading_he', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-hero-motif" className="mb-1 block text-sm">{t('design_settings_hero_motif_label')}</label>
                            <select
                                id="design-hero-motif"
                                value={form.hero_motif}
                                onChange={(e) => updateField('hero_motif', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            >
                                {MOTIF_OPTIONS.map((motif) => (
                                    <option key={motif} value={motif}>
                                        {t(`design_settings_motif_${motif.replace(/-/g, '_')}`)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="mx-auto h-24 w-24 sm:mx-0">
                            <DesignArt motif={form.hero_motif} className="rounded" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 font-serif text-lg">{t('design_settings_stats_section_title')}</h2>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label htmlFor="design-stat-pieces" className="mb-1 block text-sm">{t('design_settings_stat_pieces_shipped_label')}</label>
                            <input
                                id="design-stat-pieces"
                                type="number"
                                min="0"
                                required
                                value={form.stat_pieces_shipped}
                                onChange={(e) => updateField('stat_pieces_shipped', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-stat-rating" className="mb-1 block text-sm">{t('design_settings_stat_rating_label')}</label>
                            <input
                                id="design-stat-rating"
                                type="number"
                                min="0"
                                max="5"
                                step="0.1"
                                required
                                value={form.stat_rating}
                                onChange={(e) => updateField('stat_rating', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="design-stat-countries" className="mb-1 block text-sm">{t('design_settings_stat_countries_label')}</label>
                            <input
                                id="design-stat-countries"
                                type="number"
                                min="0"
                                required
                                value={form.stat_countries}
                                onChange={(e) => updateField('stat_countries', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                    </div>
                </section>

                {status === 'saved' && <p role="status" className="text-sm text-green-700">{t('design_settings_saved')}</p>}
                {status === 'error' && <p role="alert" className="text-sm text-red-700">{t('design_settings_error')}</p>}

                <button
                    type="submit"
                    disabled={saving || !colorIsValid}
                    className="rounded bg-ink px-6 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                >
                    {t('design_settings_save')}
                </button>
            </form>
        </div>
    );
}
