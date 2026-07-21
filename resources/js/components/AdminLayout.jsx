import AdminSidebar from './AdminSidebar';

export default function AdminLayout({ children }) {
    return (
        <div className="mx-auto flex max-w-7xl gap-8 px-6 py-10">
            <AdminSidebar />
            <div className="min-w-0 flex-1">{children}</div>
        </div>
    );
}
