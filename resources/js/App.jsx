import { lazy, Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { PayPalScriptProvider } from '@paypal/react-paypal-js';
import { AuthProvider, useAuth } from './lib/AuthContext';
import { SiteSettingsProvider } from './lib/SiteSettingsContext';
import { WishlistProvider } from './lib/WishlistContext';
import Layout from './Layout';
import ErrorBoundary from './components/ErrorBoundary';
import RouteLoading from './components/RouteLoading';
// Catalog is the landing page for most visits (and the page Lighthouse mobile
// audits run against), so it stays a static import — every other route is
// lazy-loaded into its own chunk so catalog/product visitors don't pay for
// admin dashboard, chat, checkout, and PayPal SDK code they never run. A
// mobile Lighthouse run against the pre-split bundle measured 430 KB of JS
// (135 KB gzip) with ~191 KB of it unused on the catalog page alone.
import Catalog from './pages/Catalog';

const ProductDetail = lazy(() => import('./pages/ProductDetail'));
const Login = lazy(() => import('./pages/Login'));
const Register = lazy(() => import('./pages/Register'));
const ForgotPassword = lazy(() => import('./pages/ForgotPassword'));
const ResetPassword = lazy(() => import('./pages/ResetPassword'));
const Checkout = lazy(() => import('./pages/Checkout'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const ProjectProgress = lazy(() => import('./pages/ProjectProgress'));
const VisionerChat = lazy(() => import('./pages/VisionerChat'));
const StyleGuide = lazy(() => import('./pages/StyleGuide'));
const DesignSettings = lazy(() => import('./pages/DesignSettings'));
const Orders = lazy(() => import('./pages/Orders'));
const AccountSettings = lazy(() => import('./pages/AccountSettings'));
const Wishlist = lazy(() => import('./pages/Wishlist'));
const About = lazy(() => import('./pages/About'));
const Privacy = lazy(() => import('./pages/Privacy'));
const Terms = lazy(() => import('./pages/Terms'));
const NotFound = lazy(() => import('./pages/NotFound'));

function RequireAdmin({ children }) {
    const { user, loading } = useAuth();

    if (loading) return null;
    if (!user || user.role !== 'admin') return <Navigate to="/login" replace />;

    return children;
}

function RequireAuth({ children }) {
    const { user, loading } = useAuth();

    if (loading) return null;
    if (!user) return <Navigate to="/login" replace />;

    return children;
}

// PayPal's SDK is a same-origin-adjacent third-party <script> (paypal.com) that
// only Checkout needs — a mobile Lighthouse run showed it loading (and mostly
// going unused) on every page when PayPalScriptProvider wrapped the whole app.
// Scoping it to just this route means catalog/product/login/etc. visitors never
// fetch it at all.
function CheckoutWithPayPal() {
    return (
        <PayPalScriptProvider
            options={{ clientId: import.meta.env.VITE_PAYPAL_CLIENT_ID || 'test', currency: 'USD' }}
        >
            <Checkout />
        </PayPalScriptProvider>
    );
}

export default function App() {
    return (
        <AuthProvider>
            <SiteSettingsProvider>
                <WishlistProvider>
                    <BrowserRouter>
                        <Layout>
                            <ErrorBoundary>
                                <Suspense fallback={<RouteLoading />}>
                                    <Routes>
                                        <Route path="/" element={<Catalog />} />
                                        <Route path="/about" element={<About />} />
                                        <Route path="/privacy" element={<Privacy />} />
                                        <Route path="/terms" element={<Terms />} />
                                        <Route path="/products/:slug" element={<ProductDetail />} />
                                        <Route path="/login" element={<Login />} />
                                        <Route path="/register" element={<Register />} />
                                        <Route path="/forgot-password" element={<ForgotPassword />} />
                                        <Route path="/reset-password" element={<ResetPassword />} />
                                        <Route path="/checkout/:productId" element={<CheckoutWithPayPal />} />
                                        <Route
                                            path="/orders"
                                            element={
                                                <RequireAuth>
                                                    <Orders />
                                                </RequireAuth>
                                            }
                                        />
                                        <Route
                                            path="/wishlist"
                                            element={
                                                <RequireAuth>
                                                    <Wishlist />
                                                </RequireAuth>
                                            }
                                        />
                                        <Route
                                            path="/account"
                                            element={
                                                <RequireAuth>
                                                    <AccountSettings />
                                                </RequireAuth>
                                            }
                                        />
                                        <Route
                                            path="/dashboard"
                                            element={
                                                <RequireAdmin>
                                                    <Dashboard />
                                                </RequireAdmin>
                                            }
                                        />
                                        <Route
                                            path="/dashboard/progress"
                                            element={
                                                <RequireAdmin>
                                                    <ProjectProgress />
                                                </RequireAdmin>
                                            }
                                        />
                                        <Route
                                            path="/dashboard/chat"
                                            element={
                                                <RequireAdmin>
                                                    <VisionerChat />
                                                </RequireAdmin>
                                            }
                                        />
                                        <Route
                                            path="/dashboard/style-guide"
                                            element={
                                                <RequireAdmin>
                                                    <StyleGuide />
                                                </RequireAdmin>
                                            }
                                        />
                                        <Route
                                            path="/dashboard/design"
                                            element={
                                                <RequireAdmin>
                                                    <DesignSettings />
                                                </RequireAdmin>
                                            }
                                        />
                                        <Route path="*" element={<NotFound />} />
                                    </Routes>
                                </Suspense>
                            </ErrorBoundary>
                        </Layout>
                    </BrowserRouter>
                </WishlistProvider>
            </SiteSettingsProvider>
        </AuthProvider>
    );
}
