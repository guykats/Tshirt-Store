import TeamManagementNav from './TeamManagementNav';

// Team Management (Board/Epics/Chat) is a deliberately distinct
// sub-application — permanently dark, not a themeable toggle, and rendered
// outside the storefront's <Layout> entirely (see App.jsx) rather than just
// re-skinned inside it, so it reads as its own tool rather than a dark page
// bolted onto the store. `.team-dark` (resources/css/app.css) defines the
// --tm-* custom properties this component tree draws from; it's scoped to
// this class only, never :root, so the storefront/store-admin light theme
// can never be affected by it.
export default function TeamManagementLayout({ children }) {
    return (
        <div className="team-dark min-h-screen bg-[var(--tm-bg)] text-[var(--tm-text)]">
            <TeamManagementNav />
            <main className="mx-auto max-w-6xl px-6 py-10">{children}</main>
        </div>
    );
}
