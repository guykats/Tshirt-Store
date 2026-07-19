import '@testing-library/jest-dom/vitest';
import { afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';

// Unmount any component rendered by a test before the next one runs, so
// document.title/dir/lang/localStorage mutations (Layout.jsx's toggleLocale,
// useDocumentMeta) never leak between test files.
afterEach(() => {
    cleanup();
});
