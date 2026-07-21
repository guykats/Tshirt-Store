import { NavLink } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const LINKS = [
    { to: '/dashboard/progress', key: 'nav_board' },
    { to: '/dashboard/epics', key: 'nav_epics' },
    { to: '/dashboard/chat', key: 'nav_chat' },
];

export default function TeamManagementSidebar() {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('nav_team_management')} className="w-44 shrink-0 space-y-1">
            {LINKS.map((link) => (
                <NavLink
                    key={link.to}
                    to={link.to}
                    className={({ isActive }) =>
                        `block rounded px-3 py-2 text-sm ${
                            isActive ? 'bg-ink text-white' : 'text-ink-soft hover:bg-parchment-dim hover:text-ink'
                        }`
                    }
                >
                    {t(link.key)}
                </NavLink>
            ))}
        </nav>
    );
}
