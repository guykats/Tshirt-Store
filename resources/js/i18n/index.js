import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
    en: {
        translation: {
            app_name: 'Tshirt Store',
            nav_catalog: 'Catalog',
            nav_dashboard: 'Dashboard',
            nav_login: 'Login',
            nav_logout: 'Logout',
            login_title: 'Admin / Staff Login',
            email: 'Email',
            password: 'Password',
            login_button: 'Log in',
            login_error: 'Invalid email or password.',
            catalog_title: 'Catalog',
            catalog_empty: 'No products available yet.',
            dashboard_title: 'Approval Dashboard',
            dashboard_designs: 'Pending Designs',
            dashboard_orders: 'Pending Orders',
            approve: 'Approve',
            reject: 'Reject',
            no_pending_designs: 'No designs awaiting approval.',
            no_pending_orders: 'No orders awaiting approval.',
        },
    },
    he: {
        translation: {
            app_name: 'חנות החולצות',
            nav_catalog: 'קטלוג',
            nav_dashboard: 'לוח בקרה',
            nav_login: 'התחברות',
            nav_logout: 'התנתקות',
            login_title: 'כניסת צוות / מנהל',
            email: 'אימייל',
            password: 'סיסמה',
            login_button: 'התחבר',
            login_error: 'אימייל או סיסמה שגויים.',
            catalog_title: 'קטלוג',
            catalog_empty: 'אין מוצרים זמינים כרגע.',
            dashboard_title: 'לוח אישורים',
            dashboard_designs: 'עיצובים ממתינים לאישור',
            dashboard_orders: 'הזמנות ממתינות לאישור',
            approve: 'אישור',
            reject: 'דחייה',
            no_pending_designs: 'אין עיצובים הממתינים לאישור.',
            no_pending_orders: 'אין הזמנות הממתינות לאישור.',
        },
    },
};

i18n.use(initReactI18next).init({
    resources,
    lng: localStorage.getItem('locale') || 'en',
    fallbackLng: 'en',
    interpolation: { escapeValue: false },
});

export default i18n;
