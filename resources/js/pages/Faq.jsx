import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const CATEGORIES = [
    {
        title: 'faq_cat_sizing_title',
        items: [
            { q: 'faq_sizing_q1', a: 'faq_sizing_a1' },
            { q: 'faq_sizing_q2', a: 'faq_sizing_a2' },
        ],
    },
    {
        title: 'faq_cat_shipping_title',
        items: [
            { q: 'faq_shipping_q1', a: 'faq_shipping_a1' },
            { q: 'faq_shipping_q2', a: 'faq_shipping_a2' },
        ],
    },
    {
        title: 'faq_cat_returns_title',
        items: [
            { q: 'faq_returns_q1', a: 'faq_returns_a1' },
            { q: 'faq_returns_q2', a: 'faq_returns_a2' },
        ],
    },
    {
        title: 'faq_cat_payment_title',
        items: [
            { q: 'faq_payment_q1', a: 'faq_payment_a1' },
            { q: 'faq_payment_q2', a: 'faq_payment_a2' },
        ],
    },
    {
        title: 'faq_cat_tracking_title',
        items: [
            { q: 'faq_tracking_q1', a: 'faq_tracking_a1' },
            { q: 'faq_tracking_q2', a: 'faq_tracking_a2' },
        ],
    },
    {
        title: 'faq_cat_contact_title',
        items: [{ q: 'faq_contact_q1', a: 'faq_contact_a1' }],
    },
];

function FaqItem({ id, question, answer, open, onToggle }) {
    const panelId = `${id}-panel`;
    const buttonId = `${id}-button`;

    return (
        <div className="border-b border-line">
            <h3 className="m-0">
                <button
                    id={buttonId}
                    type="button"
                    aria-expanded={open}
                    aria-controls={panelId}
                    onClick={onToggle}
                    className="flex w-full items-center justify-between gap-4 py-4 text-start font-medium text-ink hover:text-brass"
                >
                    <span>{question}</span>
                    <span aria-hidden="true" className="shrink-0 text-brass">
                        {open ? '−' : '+'}
                    </span>
                </button>
            </h3>
            <div id={panelId} role="region" aria-labelledby={buttonId} hidden={!open} className="pb-4 leading-relaxed text-ink-soft">
                <p>{answer}</p>
            </div>
        </div>
    );
}

export default function Faq() {
    const { t } = useTranslation();
    const [openId, setOpenId] = useState(null);

    useDocumentMeta(
        t('meta_faq_title', { app: t('app_name') }),
        t('meta_faq_description', { app: t('app_name') })
    );

    return (
        <div className="mx-auto max-w-2xl px-6 py-16">
            <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('faq_eyebrow')}</p>
            <h1 className="mb-8 font-serif text-3xl">{t('faq_title')}</h1>

            <div className="mb-10 flex justify-center">
                <DesignArt motif="menorah" className="h-32 w-32 rounded" label={t('faq_art_label')} />
            </div>

            <p className="mb-10 leading-relaxed text-ink-soft">{t('faq_intro', { app: t('app_name') })}</p>

            <div className="space-y-10">
                {CATEGORIES.map((category) => (
                    <section key={category.title}>
                        <h2 className="mb-2 font-serif text-xl text-ink">{t(category.title)}</h2>
                        <div>
                            {category.items.map((item) => {
                                const id = item.q;
                                return (
                                    <FaqItem
                                        key={id}
                                        id={id}
                                        question={t(item.q)}
                                        answer={t(item.a)}
                                        open={openId === id}
                                        onToggle={() => setOpenId(openId === id ? null : id)}
                                    />
                                );
                            })}
                        </div>
                    </section>
                ))}
            </div>

            <p className="mt-12 text-sm text-ink-soft">{t('faq_still_need_help', { app: t('app_name') })}</p>
        </div>
    );
}
