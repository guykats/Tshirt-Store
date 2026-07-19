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
                        <>
                            <Link to="/dashboard" className="hover:text-ink">{t('nav_dashboard')}</Link>
                            <Link to="/dashboard/progress" className="hover:text-ink">{t('nav_progress')}</Link>
                            <Link to="/dashboard/chat" className="hover:text-ink">{t('nav_chat')}</Link>
                            <Link to="/dashboard/style-guide" className="hover:text-ink">{t('nav_style_guide')}</Link>
                            <Link to="/dashboard/design" className="hover:text-ink">{t('nav_design')}</Link>
                            <Link to="/dashboard/products" className="hover:text-ink">{t('nav_products')}</Link>
                        </>
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
            <footer className="mt-24 border-t border-line px-6 py-10 text-center text-xs text-ink-soft">
                <p>{t('footer_tagline')}</p>
                <div className="mt-3 flex items-center justify-center gap-4">
                    <Link to="/faq" className="hover:text-ink">{t('footer_faq_link')}</Link>
                    <Link to="/privacy" className="hover:text-ink">{t('footer_privacy_link')}</Link>
                    <Link to="/terms" className="hover:text-ink">{t('footer_terms_link')}</Link>
                </div>
            </footer>
        </div>
    );
}
