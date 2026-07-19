import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import api from './api';
import { useAuth } from './AuthContext';

const WishlistContext = createContext({
    productIds: new Set(),
    loading: true,
    isWishlisted: () => false,
    toggle: async () => {},
});

export function WishlistProvider({ children }) {
    const { user } = useAuth();
    const [productIds, setProductIds] = useState(new Set());
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(() => {
        if (!user) {
            setProductIds(new Set());
            setLoading(false);
            return Promise.resolve();
        }

        setLoading(true);
        return api.get('/api/wishlist')
            .then((res) => {
                setProductIds(new Set(res.data.data.map((item) => item.product.id)));
            })
            .catch(() => setProductIds(new Set()))
            .finally(() => setLoading(false));
    }, [user]);

    useEffect(() => {
        refresh();
    }, [refresh]);

    const isWishlisted = useCallback((productId) => productIds.has(productId), [productIds]);

    // Toggles by product slug (the API's route-model-binding key) while tracking
    // state by id, so the catalog/product-detail card only needs the same
    // product object it already renders.
    const toggle = useCallback(async (product) => {
        if (!user) return;

        const wishlisted = productIds.has(product.id);

        // Optimistic update — the add/remove endpoints are idempotent-ish
        // (re-adding an existing item just returns it, removing a missing
        // one is a no-op), so a failed request just gets silently re-synced
        // on the next refresh rather than needing complex rollback UI.
        setProductIds((prev) => {
            const next = new Set(prev);
            if (wishlisted) {
                next.delete(product.id);
            } else {
                next.add(product.id);
            }
            return next;
        });

        try {
            if (wishlisted) {
                await api.delete(`/api/products/${product.slug}/wishlist`);
            } else {
                await api.post(`/api/products/${product.slug}/wishlist`);
            }
        } catch {
            await refresh();
        }
    }, [user, productIds, refresh]);

    const value = useMemo(
        () => ({ productIds, loading, isWishlisted, toggle, refresh }),
        [productIds, loading, isWishlisted, toggle, refresh],
    );

    return (
        <WishlistContext.Provider value={value}>
            {children}
        </WishlistContext.Provider>
    );
}

export function useWishlist() {
    return useContext(WishlistContext);
}
