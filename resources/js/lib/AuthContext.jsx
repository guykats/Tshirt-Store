import { createContext, useContext, useEffect, useState } from 'react';
import api, { ensureCsrfCookie } from './api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        api.get('/api/me')
            .then((res) => setUser(res.data.data))
            .catch(() => setUser(null))
            .finally(() => setLoading(false));
    }, []);

    async function login(email, password) {
        await ensureCsrfCookie();
        await api.post('/api/login', { email, password });
        const res = await api.get('/api/me');
        setUser(res.data.data);
    }

    async function register(payload) {
        await ensureCsrfCookie();
        const res = await api.post('/api/register', payload);
        setUser(res.data.data);
    }

    async function logout() {
        await api.post('/api/logout');
        setUser(null);
    }

    return (
        <AuthContext.Provider value={{ user, loading, login, register, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
