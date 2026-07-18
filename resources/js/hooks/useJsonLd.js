import { useEffect } from 'react';

const SCRIPT_ID = 'jsonld-structured-data';

/**
 * Injects (and cleans up) a single <script type="application/ld+json"> tag in
 * <head> for the current page, following the same direct-DOM-manipulation
 * convention as useDocumentMeta (this SPA has no react-helmet-async). Unlike
 * the Open Graph tags — which are static in app.blade.php because most social
 * crawlers never execute JavaScript — Googlebot does render JS before reading
 * structured data, so a client-side-injected JSON-LD tag is a legitimate,
 * commonly-used approach for SPAs and will be seen by Google's indexer.
 *
 * Pass `null`/`undefined` for `data` to remove any existing tag (e.g. while a
 * page is still loading its data).
 */
export default function useJsonLd(data) {
    useEffect(() => {
        let tag = document.getElementById(SCRIPT_ID);

        if (!data) {
            if (tag) tag.remove();
            return undefined;
        }

        if (!tag) {
            tag = document.createElement('script');
            tag.type = 'application/ld+json';
            tag.id = SCRIPT_ID;
            document.head.appendChild(tag);
        }
        tag.textContent = JSON.stringify(data);

        return () => {
            tag?.remove();
        };
    }, [data]);
}
