import { NavLink } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

// Grouped, config-driven nav: a flat list of 9+ links was hard to scan in the
// top bar (the problem this replaces) and would be just as hard to scan
// stacked flat in a sidebar. Grouping under a handful of labeled sections
// keeps each group's contents to 2-3 items and puts the single most-used
// destination (the approvals/orders overview) ungrouped at the top.
const GROUPS = [
    {
        header: null,
        links: [{ to: '/dashboard', key: 'nav_dashboard' }],
    },
    {
        header: 'nav_team_management',
        links: [
            { to: '/dashboard/progress', key: 'nav_board' },
            { to: '/dashboard/epics', key: 'nav_epics' },
            { to: '/dashboard/chat', key: 'nav_chat' },
        ],
    },
    {
        header: 'nav_group_store',
        links: [
            { to: '/dashboard/products', key: 'nav_products' },
            { to: '/dashboard/coupons', key: 'nav_coupons' },
            { to: '/dashboard/reviews', key: 'nav_reviews' },
        ],
    },
    {
        header: 'nav_group_site',
        links: [
            { to: '/dashboard/design', key: 'nav_design' },
            { to: '/dashboard/style-guide', key: 'nav_style_guide' },
        ],
    },
    {
        header: 'nav_group_system',
        links: [{ to: '/dashboard/audit-log', key: 'nav_audit_log' }],
    },
];

export default function AdminSidebar() {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('nav_dashboard')} className="w-48 shrink-0 space-y-5">
            {GROUPS.map((group, i) => (
                <div key={group.header ?? `group-${i}`}>
                    {group.header && (
                        <p className="mb-1 px-3 text-xs tracking-wide text-ink-soft uppercase">
                            {t(group.header)}
                        </p>
                    )}
                    <div className="space-y-1">
                        {group.links.map((link) => (
                            <NavLink
                                key={link.to}
                                to={link.to}
                                end={link.to === '/dashboard'}
                                className={({ isActive }) =>
                                    `block rounded px-3 py-2 text-sm ${
                                        isActive ? 'bg-ink text-white' : 'text-ink-soft hover:bg-parchment-dim hover:text-ink'
                                    }`
                                }
                            >
                                {t(link.key)}
                            </NavLink>
                        ))}
                    </div>
                </div>
            ))}
        </nav>
    );
}
