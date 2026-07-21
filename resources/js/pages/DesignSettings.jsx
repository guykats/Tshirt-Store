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
};

const EMPTY_TESTIMONIAL_FORM = {
    author_name: '',
    author_context_en: '',
    author_context_he: '',
    quote_en: '',
    quote_he: '',
    sort_order: 0,
    is_active: true,
};

export default function DesignSettings() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_design_settings_title', { app: t('app_name') }));

    const [form, setForm] = useState(EMPTY_FORM);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState(null); // 'saved' | 'error' | null

    const [testimonials, setTestimonials] = useState([]);
    const [testimonialsLoading, setTestimonialsLoading] = useState(true);
    const [testimonialForm, setTestimonialForm] = useState(EMPTY_TESTIMONIAL_FORM);
    const [editingId, setEditingId] = useState(null);
    const [testimonialSaving, setTestimonialSaving] = useState(false);
    const [testimonialStatus, setTestimonialStatus] = useState(null); // 'saved' | 'error' | null
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);

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
                });
            })
            .finally(() => setLoading(false));
        loadTestimonials();
    }, []);

    function loadTestimonials() {
        setTestimonialsLoading(true);
        return api.get('/api/testimonials/manage')
            .then((res) => setTestimonials(res.data.data))
            .finally(() => setTestimonialsLoading(false));
    }

    function startEditTestimonial(testimonial) {
        setEditingId(testimonial.id);
        setTestimonialStatus(null);
        setTestimonialForm({
            author_name: testimonial.author_name,
            author_context_en: testimonial.author_context_en,
            author_context_he: testimonial.author_context_he,
            quote_en: testimonial.quote_en,
            quote_he: testimonial.quote_he,
            sort_order: testimonial.sort_order,
            is_active: testimonial.is_active,
        });
    }

    function startNewTestimonial() {
        setEditingId('new');
        setTestimonialStatus(null);
        setTestimonialForm(EMPTY_TESTIMONIAL_FORM);
    }

    function cancelTestimonialEdit() {
        setEditingId(null);
        setTestimonialStatus(null);
        setTestimonialForm(EMPTY_TESTIMONIAL_FORM);
    }

    function updateTestimonialField(field, value) {
        setTestimonialForm((prev) => ({ ...prev, [field]: value }));
    }

    async function handleTestimonialSubmit(e) {
        e.preventDefault();
        setTestimonialStatus(null);
        setTestimonialSaving(true);
        try {
            const payload = { ...testimonialForm, sort_order: Number(testimonialForm.sort_order) };
            if (editingId === 'new') {
                await api.post('/api/testimonials', payload);
            } else {
                await api.patch(`/api/testimonials/${editingId}`, payload);
            }
            await loadTestimonials();
            setEditingId(null);
            setTestimonialForm(EMPTY_TESTIMONIAL_FORM);
            setTestimonialStatus('saved');
        } catch {
            setTestimonialStatus('error');
        } finally {
            setTestimonialSaving(false);
        }
    }

    async function handleTestimonialDelete(id) {
        setTestimonialStatus(null);
        try {
            await api.delete(`/api/testimonials/${id}`);
            setConfirmDeleteId(null);
            await loadTestimonials();
        } catch {
            setTestimonialStatus('error');
        }
    }

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
            <div className="max-w-3xl">
                <p className="text-ink-soft">…</p>
            </div>
        );
    }

    return (
        <div className="max-w-3xl">
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

            {/* Homepage stats (completed orders, average rating, countries served) are
                intentionally not editable here — they're computed live from real orders
                and reviews (see App\Http\Controllers\Api\HomeStatsController) so an admin
                can no longer type in an arbitrary investor-facing number. */}
            <section className="mt-14 border-t border-line pt-10">
                <h2 className="mb-2 font-serif text-lg">{t('design_settings_testimonials_section_title')}</h2>
                <p className="mb-6 max-w-2xl text-sm text-ink-soft">{t('design_settings_testimonials_hint')}</p>

                {testimonialStatus === 'saved' && (
                    <p role="status" className="mb-4 text-sm text-green-700">{t('design_settings_testimonial_saved')}</p>
                )}
                {testimonialStatus === 'error' && (
                    <p role="alert" className="mb-4 text-sm text-red-700">{t('design_settings_testimonial_error')}</p>
                )}

                {testimonialsLoading ? (
                    <p className="text-ink-soft">…</p>
                ) : (
                    <ul className="mb-8 space-y-4">
                        {testimonials.length === 0 && (
                            <li className="text-sm text-ink-soft">{t('design_settings_testimonials_empty')}</li>
                        )}
                        {testimonials.map((testimonial) => (
                            <li key={testimonial.id} className="rounded border border-line p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {testimonial.author_name}
                                            {!testimonial.is_active && (
                                                <span className="ms-2 text-xs text-ink-soft">
                                                    {t('design_settings_testimonial_hidden_badge')}
                                                </span>
                                            )}
                                        </p>
                                        <p className="mt-1 text-sm text-ink-soft">{testimonial.quote_en}</p>
                                    </div>
                                    <div className="flex shrink-0 gap-3">
                                        <button
                                            type="button"
                                            onClick={() => startEditTestimonial(testimonial)}
                                            className="text-sm underline"
                                        >
                                            {t('design_settings_testimonial_edit')}
                                        </button>
                                        {confirmDeleteId === testimonial.id ? (
                                            <span className="flex gap-2 text-sm">
                                                <button
                                                    type="button"
                                                    onClick={() => handleTestimonialDelete(testimonial.id)}
                                                    className="text-red-700 underline"
                                                >
                                                    {t('design_settings_testimonial_confirm_delete_yes')}
                                                </button>
                                                <button type="button" onClick={() => setConfirmDeleteId(null)} className="underline">
                                                    {t('design_settings_testimonial_confirm_delete_no')}
                                                </button>
                                            </span>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => setConfirmDeleteId(testimonial.id)}
                                                className="text-sm text-red-700 underline"
                                            >
                                                {t('design_settings_testimonial_delete')}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {editingId === null && (
                    <button
                        type="button"
                        onClick={startNewTestimonial}
                        className="rounded border border-ink px-5 py-2.5 text-sm tracking-wide uppercase hover:bg-parchment-dim"
                    >
                        {t('design_settings_testimonial_add')}
                    </button>
                )}

                {editingId !== null && (
                    <form onSubmit={handleTestimonialSubmit} className="max-w-xl space-y-4 rounded border border-line p-5">
                        <h3 className="font-serif text-base">
                            {editingId === 'new' ? t('design_settings_testimonial_new_title') : t('design_settings_testimonial_edit')}
                        </h3>
                        <div>
                            <label htmlFor="testimonial-author-name" className="mb-1 block text-sm">
                                {t('design_settings_testimonial_author_name_label')}
                            </label>
                            <input
                                id="testimonial-author-name"
                                type="text"
                                required
                                value={testimonialForm.author_name}
                                onChange={(e) => updateTestimonialField('author_name', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="testimonial-context-en" className="mb-1 block text-sm">
                                    {t('design_settings_testimonial_context_en_label')}
                                </label>
                                <input
                                    id="testimonial-context-en"
                                    type="text"
                                    required
                                    value={testimonialForm.author_context_en}
                                    onChange={(e) => updateTestimonialField('author_context_en', e.target.value)}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2"
                                />
                            </div>
                            <div>
                                <label htmlFor="testimonial-context-he" className="mb-1 block text-sm">
                                    {t('design_settings_testimonial_context_he_label')}
                                </label>
                                <input
                                    id="testimonial-context-he"
                                    type="text"
                                    dir="rtl"
                                    required
                                    value={testimonialForm.author_context_he}
                                    onChange={(e) => updateTestimonialField('author_context_he', e.target.value)}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2"
                                />
                            </div>
                        </div>
                        <div>
                            <label htmlFor="testimonial-quote-en" className="mb-1 block text-sm">
                                {t('design_settings_testimonial_quote_en_label')}
                            </label>
                            <textarea
                                id="testimonial-quote-en"
                                required
                                rows={3}
                                value={testimonialForm.quote_en}
                                onChange={(e) => updateTestimonialField('quote_en', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="testimonial-quote-he" className="mb-1 block text-sm">
                                {t('design_settings_testimonial_quote_he_label')}
                            </label>
                            <textarea
                                id="testimonial-quote-he"
                                required
                                dir="rtl"
                                rows={3}
                                value={testimonialForm.quote_he}
                                onChange={(e) => updateTestimonialField('quote_he', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="testimonial-sort-order" className="mb-1 block text-sm">
                                    {t('design_settings_testimonial_sort_order_label')}
                                </label>
                                <input
                                    id="testimonial-sort-order"
                                    type="number"
                                    min="0"
                                    required
                                    value={testimonialForm.sort_order}
                                    onChange={(e) => updateTestimonialField('sort_order', e.target.value)}
                                    className="w-full rounded border border-line bg-parchment px-3 py-2"
                                />
                            </div>
                            <div className="flex items-end pb-2">
                                <label htmlFor="testimonial-active" className="flex items-center gap-2 text-sm">
                                    <input
                                        id="testimonial-active"
                                        type="checkbox"
                                        checked={testimonialForm.is_active}
                                        onChange={(e) => updateTestimonialField('is_active', e.target.checked)}
                                    />
                                    {t('design_settings_testimonial_active_label')}
                                </label>
                            </div>
                        </div>
                        <div className="flex gap-4">
                            <button
                                type="submit"
                                disabled={testimonialSaving}
                                className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                            >
                                {t('design_settings_testimonial_save')}
                            </button>
                            <button type="button" onClick={cancelTestimonialEdit} className="text-sm underline">
                                {t('design_settings_testimonial_cancel')}
                            </button>
                        </div>
                    </form>
                )}
            </section>
        </div>
    );
}
