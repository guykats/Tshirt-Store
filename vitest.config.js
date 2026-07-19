import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Deliberately separate from vite.config.js: the real build config wires in
// laravel-vite-plugin (which expects a running dev server / manifest) and
// @tailwindcss/vite, neither of which Vitest's jsdom test environment needs
// or can satisfy. Keeping this config minimal (just the React plugin, for
// JSX transform) avoids dragging Laravel/Tailwind tooling into unit tests.
export default defineConfig({
    plugins: [react()],
    test: {
        environment: 'jsdom',
        setupFiles: ['./resources/js/testSetup.js'],
        globals: false,
        css: false,
    },
});
