import StoreAdminNav from './StoreAdminNav';

// Replaces the old AdminLayout (sidebar + content column) for the 7
// store-admin pages. Light parchment/ink/brass theme, unchanged from the
// rest of the site — only the navigation shape changed, from a sidebar to a
// horizontal top bar (see StoreAdminNav).
export default function StoreAdminLayout({ children }) {
    return (
        <div className="mx-auto max-w-7xl px-6 py-10">
            <StoreAdminNav />
            <div className="min-w-0">{children}</div>
        </div>
    );
}
