import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './lib/AuthContext';
import Layout from './Layout';
import Catalog from './pages/Catalog';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';

function RequireAdmin({ children }) {
    const { user, loading } = useAuth();

    if (loading) return null;
    if (!user || user.role !== 'admin') return <Navigate to="/login" replace />;

    return children;
}

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Layout>
                    <Routes>
                        <Route path="/" element={<Catalog />} />
                        <Route path="/login" element={<Login />} />
                        <Route
                            path="/dashboard"
                            element={
                                <RequireAdmin>
                                    <Dashboard />
                                </RequireAdmin>
                            }
                        />
                    </Routes>
                </Layout>
            </BrowserRouter>
        </AuthProvider>
    );
}
