import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import { useWishlist } from '../lib/WishlistContext';

/**
 * A heart-shaped save/unsave toggle for a product, used on both the catalog
 * card and the product detail page. Logged-out visitors are routed to login
 * rather than the button silently no-op'ing on click, since the underlying
 * endpoints require a Sanctum session.
 */
export default function WishlistButton({ product, className = '' }) {
    const { t } = useTranslation();
    const { user } = useAuth();
    const { isWishlisted, toggle } = useWishlist();
    const navigate = useNavigate();

    const wishlisted = user ? isWishlisted(product.id) : false;

    function handleClick(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!user) {
            navigate('/login');
            return;
        }

        toggle(product);
    }

    const label = wishlisted
        ? t('wishlist_remove_aria', { name: product.name })
        : t('wishlist_add_aria', { name: product.name });

    return (
        <button
            type="button"
            onClick={handleClick}
            aria-label={label}
            aria-pressed={wishlisted}
            className={`flex h-9 w-9 items-center justify-center rounded-full border border-line bg-parchment/90 transition-colors hover:border-brass ${className}`}
        >
            <svg
                viewBox="0 0 24 24"
                className="h-5 w-5"
                aria-hidden="true"
                fill={wishlisted ? 'var(--color-brass)' : 'none'}
                stroke={wishlisted ? 'var(--color-brass)' : 'var(--color-ink)'}
                strokeWidth="1.8"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 20.25c-.3 0-.59-.09-.84-.26C7.9 17.86 3 14.24 3 9.75 3 7.13 5.11 5 7.72 5c1.5 0 2.94.71 3.84 1.86l.44.56.44-.56C13.34 5.71 14.78 5 16.28 5 18.89 5 21 7.13 21 9.75c0 4.49-4.9 8.11-8.16 10.24-.25.17-.54.26-.84.26Z"
                />
            </svg>
        </button>
    );
}
