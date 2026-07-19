import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import api from './api';

const SiteSettingsContext = createContext({ settings: null, loading: true, refresh: () => {} });

export function SiteSettingsProvider({ children }) {
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(() => {
        return api.get('/api/site-settings')
            .then((res) => setSettings(res.data.data))
            .catch(() => setSettings(null));
    }, []);

    useEffect(() => {
        refresh().finally(() => setLoading(false));
    }, [refresh]);

    return (
        <SiteSettingsContext.Provider value={{ settings, loading, refresh }}>
            {children}
        </SiteSettingsContext.Provider>
    );
}

export function useSiteSettings() {
    return useContext(SiteSettingsContext);
}
