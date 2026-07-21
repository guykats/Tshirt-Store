import js from '@eslint/js';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

// Flat config (ESLint 9+). This is a first pass at linting for this repo —
// browser/JSX source under resources/js gets the React + Hooks rule sets,
// Node-context build config files get Node globals instead, and generated
// output is ignored entirely. Deliberately not chasing every stylistic rule
// here; the goal is catching real bugs (undefined vars, broken hooks rules,
// duplicate JSX props/keys) without a large one-off reformatting pass.
export default [
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/build/**',
            'storage/**',
            'bootstrap/cache/**',
            'coverage/**',
        ],
    },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.{js,jsx}'],
        plugins: {
            react,
            'react-hooks': reactHooks,
        },
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            parserOptions: {
                ecmaFeatures: { jsx: true },
            },
            globals: {
                ...globals.browser,
                ...globals.es2021,
            },
        },
        settings: {
            react: { version: 'detect' },
        },
        rules: {
            ...react.configs.flat.recommended.rules,
            ...react.configs.flat['jsx-runtime'].rules,
            ...reactHooks.configs['recommended-legacy'].rules,
            // This codebase doesn't use PropTypes (no prop-types dependency,
            // plain JSX components throughout) — enforcing it here would be
            // a large unrelated migration, not a lint-tooling task.
            'react/prop-types': 'off',
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
        },
    },
    {
        files: ['resources/js/**/__tests__/**/*.{js,jsx}', 'resources/js/testSetup.js'],
        languageOptions: {
            globals: {
                ...globals.node,
            },
        },
    },
    {
        files: ['*.config.js', 'vite.config.js', 'vitest.config.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.node,
            },
        },
    },
];
