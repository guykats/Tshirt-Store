import { NavLink, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../lib/AuthContext';

// Exactly 3 top-level sections (Board, Epics, Chat) is a clean fit for a top
// tab bar rather than a sidebar — sidebars earn their keep with many/deep
// sections, top bars with a handful of primary areas (research cited in the
// task description).
const TABS = [
    { to: '/dashboard/progress', key: 'nav_board', end: true },
    { to: '/dashboard/epics', key: 'nav_epics', end: true },
    { to: '/dashboard/chat', key: 'nav_chat', end: true },
];

// Shared keyboard-focus treatment for every interactive element in this
// dark theme: the browser's default focus outline reads poorly against a
// near-black surface, so every focusable control here gets an explicit,
// high-contrast accent-colored ring instead of relying on the UA default.
const FOCUS_RING =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--tm-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--tm-surface-1)]';

const tabClass = ({ isActive }) =>
    `rounded-t border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap transition-colors ${FOCUS_RING} ${
        isActive
            ? 'border-[var(--tm-accent)] text-[var(--tm-text)]'
            : 'border-transparent text-[var(--tm-text-muted)] hover:text-[var(--tm-text)]'
    }`;

export default function TeamManagementNav() {
    const { t, i18n } = useTranslation();
    const { user, logout } = useAuth();

    function toggleLocale() {
        const next = i18n.language === 'en' ? 'he' : 'en';
        i18n.changeLanguage(next);
        localStorage.setItem('locale', next);
        document.documentElement.dir = next === 'he' ? 'rtl' : 'ltr';
        document.documentElement.lang = next;
    }

    return (
        <header className="border-b border-[var(--tm-border)] bg-[var(--tm-surface-1)]">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
                <Link
                    to="/dashboard/progress"
                    className={`flex items-center gap-2 rounded font-sans text-sm font-semibold tracking-wide text-[var(--tm-text)] ${FOCUS_RING}`}
                >
                    <span
                        aria-hidden="true"
                        className="flex h-6 w-6 items-center justify-center rounded-md bg-[var(--tm-accent-strong)] text-xs font-bold text-white"
                    >
                        TM
                    </span>
                    {t('nav_team_management')}
                </Link>
                <div className="flex items-center gap-4 text-xs text-[var(--tm-text-muted)]">
                    {user && (
                        <button
                            type="button"
                            onClick={logout}
                            className={`rounded px-1 hover:text-[var(--tm-text)] ${FOCUS_RING}`}
                        >
                            {t('nav_logout')}
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={toggleLocale}
                        className={`rounded-full border border-[var(--tm-border-strong)] px-3 py-1 hover:border-[var(--tm-accent)] hover:text-[var(--tm-text)] ${FOCUS_RING}`}
                    >
                        {i18n.language === 'en' ? 'עברית' : 'English'}
                    </button>
                    <Link
                        to="/dashboard"
                        className={`rounded-full border border-[var(--tm-border-strong)] px-3 py-1 hover:border-[var(--tm-accent)] hover:text-[var(--tm-text)] ${FOCUS_RING}`}
                    >
                        {t('team_management_exit')}
                    </Link>
                </div>
            </div>
            <nav aria-label={t('nav_team_management')} className="mx-auto flex max-w-6xl gap-6 px-6">
                {TABS.map((tab) => (
                    <NavLink key={tab.to} to={tab.to} end={tab.end} className={tabClass}>
                        {t(tab.key)}
                    </NavLink>
                ))}
            </nav>
        </header>
    );
}
