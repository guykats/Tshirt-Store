import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from './lib/AuthContext';

export default function Layout({ children }) {
    const { t, i18n } = useTranslation();
    const { user, logout } = useAuth();
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

    return (
        <div className="min-h-screen bg-parchment text-ink">
            <nav className="flex items-center justify-between border-b border-line px-6 py-5">
                <Link to="/" className="font-serif text-xl tracking-wide">
                    {t('app_name')}
                </Link>
                <div className="flex items-center gap-5 text-sm text-ink-soft">
                    <Link to="/" className="hover:text-ink">{t('nav_catalog')}</Link>
                    {user?.role === 'admin' && (
                        <Link to="/dashboard" className="hover:text-ink">{t('nav_dashboard')}</Link>
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
                {t('footer_tagline')}
            </footer>
        </div>
    );
}
