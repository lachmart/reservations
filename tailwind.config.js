import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                display: ['"Bricolage Grotesque"', ...defaultTheme.fontFamily.sans],
                sans: ['"Hanken Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                paper: 'var(--paper)',
                surface: 'var(--surface)',
                ink: 'var(--ink)',
                muted: 'var(--muted)',
                faint: 'var(--faint)',
                line: 'var(--line)',
                accent: 'var(--accent)',
                'accent-ink': 'var(--accent-ink)',
                'accent-soft': 'var(--accent-soft)',
            },
            fontSize: {
                'display-xl': ['clamp(2.6rem, 1.6rem + 4.4vw, 5rem)', { lineHeight: '0.98', letterSpacing: '-0.02em' }],
                'display-lg': ['clamp(2rem, 1.4rem + 2.6vw, 3.25rem)', { lineHeight: '1.02', letterSpacing: '-0.02em' }],
                'display-md': ['clamp(1.5rem, 1.2rem + 1.4vw, 2.1rem)', { lineHeight: '1.08', letterSpacing: '-0.01em' }],
            },
            boxShadow: {
                card: '0 1px 2px oklch(0.2 0.03 60 / 0.05), 0 8px 24px -12px oklch(0.2 0.03 60 / 0.18)',
                lift: '0 2px 4px oklch(0.2 0.03 60 / 0.06), 0 18px 40px -18px oklch(0.2 0.03 60 / 0.28)',
            },
            transitionTimingFunction: {
                'out-quart': 'cubic-bezier(0.25, 1, 0.5, 1)',
            },
        },
    },
    plugins: [],
};
