import { NavLink, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

// A flat horizontal top bar replaces the old sidebar for the 7 store-admin
// pages. 7 links is still a lot for one row, so they're grouped with a thin
// vertical divider between each cluster (day-to-day store ops, occasional
// site config, infrequent system pages) rather than stacked in a column —
// see StoreAdminNav vs. the old AdminSidebar grouping this replaces.
const GROUPS = [
    { links: [{ to: '/dashboard', key: 'nav_dashboard', end: true }] },
    {
        links: [
            { to: '/dashboard/products', key: 'nav_products' },
            { to: '/dashboard/coupons', key: 'nav_coupons' },
            { to: '/dashboard/reviews', key: 'nav_reviews' },
        ],
    },
    {
        links: [
            { to: '/dashboard/design', key: 'nav_design' },
            { to: '/dashboard/style-guide', key: 'nav_style_guide' },
        ],
    },
    { links: [{ to: '/dashboard/audit-log', key: 'nav_audit_log' }] },
];

const linkClass = ({ isActive }) =>
    `whitespace-nowrap rounded-full px-3 py-1.5 text-sm transition-colors ${
        isActive ? 'bg-ink text-parchment' : 'text-ink-soft hover:bg-parchment-dim hover:text-ink'
    }`;

export default function StoreAdminNav() {
    const { t } = useTranslation();

    return (
        <nav
            aria-label={t('store_admin_nav_label')}
            className="mb-8 flex flex-wrap items-center gap-y-2 border-b border-line pb-4"
        >
            <div className="flex flex-wrap items-center gap-1">
                {GROUPS.map((group, i) => (
                    <div key={group.links[0].to} className="flex flex-wrap items-center gap-1">
                        {i > 0 && <span aria-hidden="true" className="mx-2 h-5 w-px shrink-0 bg-line" />}
                        {group.links.map((link) => (
                            <NavLink key={link.to} to={link.to} end={link.end} className={linkClass}>
                                {t(link.key)}
                            </NavLink>
                        ))}
                    </div>
                ))}
            </div>
            <Link
                to="/dashboard/progress"
                className="ml-auto rounded-full border border-brass/40 bg-parchment-dim px-3 py-1.5 text-sm text-ink-soft transition-colors hover:border-brass hover:text-ink"
            >
                {t('floating_admin_link')}
            </Link>
        </nav>
    );
}
