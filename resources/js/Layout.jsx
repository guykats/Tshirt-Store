import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from './lib/AuthContext';
import { useSiteSettings } from './lib/SiteSettingsContext';

export default function Layout({ children }) {
    const { t, i18n } = useTranslation();
    const { user, logout } = useAuth();
    const { settings } = useSiteSettings();
    const navigate = useNavigate();

    function toggleLocale() {
        const next = i18n.language === 'en' ? 'he' : 'en';
        i18n.changeLanguage(next);
        localStorage.setItem('locale', next);
        document.documentElement.dir = next === 'he' ? 'rtl' : 'ltr';
        document.documentElement.lang = next;
    }

    async function handleLogout() {
        await logout();
        navigate('/');
    }

    // Admin-configurable accent color (Design settings panel) re-points the same
    // --color-brass token the rest of the app already draws with, rather than
    // inventing a second color system just for this override.
    const themeStyle = settings?.accent_color ? { '--color-brass': settings.accent_color } : undefined;

    return (
        <div className="min-h-screen bg-parchment text-ink" style={themeStyle}>
            <nav className="flex items-center justify-between border-b border-line px-6 py-5">
                <Link to="/" className="flex items-center gap-2 font-serif text-xl tracking-wide">
                    {settings?.logo_url ? (
                        <img src={settings.logo_url} alt={t('app_name')} className="h-8 w-auto" />
                    ) : (
                        t('app_name')
                    )}
                </Link>
                <div className="flex items-center gap-5 text-sm text-ink-soft">
                    <Link to="/" className="hover:text-ink">{t('nav_catalog')}</Link>
                    <Link to="/about" className="hover:text-ink">{t('nav_about')}</Link>
                    {user && user.role !== 'admin' && (
                        <>
                            <Link to="/wishlist" className="hover:text-ink">{t('nav_wishlist')}</Link>
                            <Link to="/orders" className="hover:text-ink">{t('nav_orders')}</Link>
                        </>
                    )}
                    {user?.role === 'admin' && (
                        <Link to="/dashboard" className="hover:text-ink">{t('nav_dashboard')}</Link>
                    )}
                    {user && (
                        <Link to="/account" className="hover:text-ink">{t('nav_account')}</Link>
                    )}
                    {user ? (
                        <button onClick={handleLogout} className="hover:text-ink">{t('nav_logout')}</button>
                    ) : (
                        <>
                            <Link to="/login" className="hover:text-ink">{t('nav_login')}</Link>
                            <Link to="/register" className="hover:text-ink">{t('nav_register')}</Link>
                        </>
                    )}
                    <button onClick={toggleLocale} className="rounded-full border border-line px-3 py-1 text-xs hover:border-brass hover:text-ink">
                        {i18n.language === 'en' ? 'עברית' : 'English'}
                    </button>
                </div>
            </nav>
            <main>{children}</main>
            {user?.role === 'admin' && (
                <Link
                    to="/dashboard/progress"
                    aria-label={t('floating_admin_link')}
                    className="fixed right-5 bottom-5 z-40 flex items-center gap-2 rounded-full border border-brass bg-ink px-4 py-2.5 text-sm text-parchment shadow-lg transition-colors hover:bg-ink/90"
                >
                    <svg
                        viewBox="0 0 24 24"
                        className="h-4 w-4"
                        aria-hidden="true"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.8"
                    >
                        <rect x="3.5" y="4" width="17" height="16" rx="1.5" />
                        <path strokeLinecap="round" d="M8 4v16M3.5 9h4.5" />
                    </svg>
                    <span>{t('floating_admin_link')}</span>
                </Link>
            )}
            <footer className="mt-24 border-t border-line px-6 py-10 text-center text-xs text-ink-soft">
                <p>{t('footer_tagline')}</p>
                <div className="mt-3 flex items-center justify-center gap-4">
                    <Link to="/faq" className="hover:text-ink">{t('footer_faq_link')}</Link>
                    <Link to="/size-guide" className="hover:text-ink">{t('footer_size_guide_link')}</Link>
                    <Link to="/track-order" className="hover:text-ink">{t('footer_track_order_link')}</Link>
                    <Link to="/privacy" className="hover:text-ink">{t('footer_privacy_link')}</Link>
                    <Link to="/terms" className="hover:text-ink">{t('footer_terms_link')}</Link>
                </div>
            </footer>
        </div>
    );
}
