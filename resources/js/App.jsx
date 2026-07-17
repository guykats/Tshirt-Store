import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { PayPalScriptProvider } from '@paypal/react-paypal-js';
import { AuthProvider, useAuth } from './lib/AuthContext';
import Layout from './Layout';
import ErrorBoundary from './components/ErrorBoundary';
import Catalog from './pages/Catalog';
import ProductDetail from './pages/ProductDetail';
import Login from './pages/Login';
import Register from './pages/Register';
import Checkout from './pages/Checkout';
import Dashboard from './pages/Dashboard';
import ProjectProgress from './pages/ProjectProgress';
import VisionerChat from './pages/VisionerChat';
import Orders from './pages/Orders';
import About from './pages/About';

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

export default function App() {
    return (
        <AuthProvider>
            <PayPalScriptProvider
                options={{ clientId: import.meta.env.VITE_PAYPAL_CLIENT_ID || 'test', currency: 'USD' }}
            >
                <BrowserRouter>
                    <Layout>
                        <ErrorBoundary>
                            <Routes>
                                <Route path="/" element={<Catalog />} />
                                <Route path="/about" element={<About />} />
                                <Route path="/products/:slug" element={<ProductDetail />} />
                                <Route path="/login" element={<Login />} />
                                <Route path="/register" element={<Register />} />
                                <Route path="/checkout/:productId" element={<Checkout />} />
                                <Route
                                    path="/orders"
                                    element={
                                        <RequireAuth>
                                            <Orders />
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
                            </Routes>
                        </ErrorBoundary>
                    </Layout>
                </BrowserRouter>
            </PayPalScriptProvider>
        </AuthProvider>
    );
}
