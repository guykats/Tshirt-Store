import { useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

// Grouped, config-driven nav: a flat list of 9+ links was hard to scan in the
// top bar (the problem this replaces) and would be just as hard to scan
// stacked flat in a sidebar. Grouping under a handful of labeled sections
// keeps each group's contents to 2-3 items and puts the single most-used
// destination (the approvals/orders overview) ungrouped at the top.
//
// `tier` gives each group real visual weight, not just a label:
//   - 'core'     Dashboard, Team Management, Store — project tooling and the
//                pages worked in daily. Full weight, no dividers.
//   - 'settings' Design Settings + Style Guide — configured occasionally.
//                Set apart with a divider and a muted, smaller heading.
//   - 'system'   Audit Log — infrequent, technical-audience page. Most
//                deprioritized: muted, smallest text, collapsed by default
//                behind a disclosure.
const GROUPS = [
    {
        header: null,
        tier: 'core',
        links: [{ to: '/dashboard', key: 'nav_dashboard' }],
    },
    {
        header: 'nav_team_management',
        tier: 'core',
        links: [
            { to: '/dashboard/progress', key: 'nav_board' },
            { to: '/dashboard/epics', key: 'nav_epics' },
            { to: '/dashboard/chat', key: 'nav_chat' },
        ],
    },
    {
        header: 'nav_group_store',
        tier: 'core',
        links: [
            { to: '/dashboard/products', key: 'nav_products' },
            { to: '/dashboard/coupons', key: 'nav_coupons' },
            { to: '/dashboard/reviews', key: 'nav_reviews' },
        ],
    },
    {
        header: 'nav_group_site',
        tier: 'settings',
        links: [
            { to: '/dashboard/design', key: 'nav_design' },
            { to: '/dashboard/style-guide', key: 'nav_style_guide' },
        ],
    },
    {
        header: 'nav_group_system',
        tier: 'system',
        links: [{ to: '/dashboard/audit-log', key: 'nav_audit_log' }],
    },
];

const linkClass = ({ isActive }) =>
    `block rounded px-3 py-2 text-sm ${
        isActive ? 'bg-ink text-white' : 'text-ink-soft hover:bg-parchment-dim hover:text-ink'
    }`;

const mutedLinkClass = ({ isActive }) =>
    `block rounded px-3 py-1.5 text-[13px] ${
        isActive ? 'bg-ink text-white' : 'text-ink-soft/80 hover:bg-parchment-dim hover:text-ink'
    }`;

function CoreGroup({ group, t }) {
    return (
        <div>
            {group.header && (
                <p className="mb-1 px-3 text-xs tracking-wide text-ink-soft uppercase">{t(group.header)}</p>
            )}
            <div className="space-y-1">
                {group.links.map((link) => (
                    <NavLink key={link.to} to={link.to} end={link.to === '/dashboard'} className={linkClass}>
                        {t(link.key)}
                    </NavLink>
                ))}
            </div>
        </div>
    );
}

function SettingsGroup({ group, t }) {
    return (
        <div className="mt-2 border-t border-ink/10 pt-3">
            <p className="mb-1 px-3 text-[11px] tracking-wide text-ink-soft/60 uppercase">{t(group.header)}</p>
            <div className="space-y-1">
                {group.links.map((link) => (
                    <NavLink key={link.to} to={link.to} className={mutedLinkClass}>
                        {t(link.key)}
                    </NavLink>
                ))}
            </div>
        </div>
    );
}

function SystemGroup({ group, t }) {
    const location = useLocation();
    const hasActiveLink = group.links.some((link) => location.pathname.startsWith(link.to));
    const [open, setOpen] = useState(hasActiveLink);
    const panelId = 'nav-system-panel';

    return (
        <div className="mt-2 border-t border-ink/10 pt-2">
            <button
                type="button"
                onClick={() => setOpen((prev) => !prev)}
                aria-expanded={open}
                aria-controls={panelId}
                className="flex w-full items-center gap-1.5 rounded px-3 py-1 text-[10px] tracking-wide text-ink-soft/50 uppercase hover:text-ink-soft"
            >
                <svg
                    aria-hidden="true"
                    viewBox="0 0 12 12"
                    className={`h-2 w-2 shrink-0 stroke-current transition-transform duration-150 ${open ? 'rotate-90' : ''}`}
                    fill="none"
                >
                    <path d="M4 2l4 4-4 4" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                {t(group.header)}
            </button>
            {open && (
                <div id={panelId} className="mt-1 space-y-1">
                    {group.links.map((link) => (
                        <NavLink key={link.to} to={link.to} className={mutedLinkClass}>
                            {t(link.key)}
                        </NavLink>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function AdminSidebar() {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('nav_dashboard')} className="w-48 shrink-0 space-y-5">
            {GROUPS.map((group, i) => {
                const key = group.header ?? `group-${i}`;
                if (group.tier === 'settings') return <SettingsGroup key={key} group={group} t={t} />;
                if (group.tier === 'system') return <SystemGroup key={key} group={group} t={t} />;
                return <CoreGroup key={key} group={group} t={t} />;
            })}
        </nav>
    );
}
