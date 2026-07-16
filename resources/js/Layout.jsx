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
        <div>
            <nav className="flex items-center justify-between border-b border-neutral-200 px-6 py-4">
                <Link to="/" className="font-semibold">{t('app_name')}</Link>
                <div className="flex items-center gap-4 text-sm">
                    <Link to="/">{t('nav_catalog')}</Link>
                    {user?.role === 'admin' && <Link to="/dashboard">{t('nav_dashboard')}</Link>}
                    {user ? (
                        <button onClick={handleLogout}>{t('nav_logout')}</button>
                    ) : (
                        <Link to="/login">{t('nav_login')}</Link>
                    )}
                    <button onClick={toggleLocale} className="rounded border border-neutral-300 px-2 py-1">
                        {i18n.language === 'en' ? 'עברית' : 'English'}
                    </button>
                </div>
            </nav>
            <main>{children}</main>
        </div>
    );
}
