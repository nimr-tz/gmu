import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/**
 * NIMR Grants design system.
 *
 * The colour palette is replaced, not extended: only the colours below exist,
 * so a green (or any off-brand colour) cannot be used by accident. Semantic
 * names (background, card, muted, primary, ok, warn, danger, review) are what
 * views should reach for; the raw ramps are there for charts and tints.
 *
 * Tokens live as CSS variables in resources/css/app.css.
 */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/layouts/app.blade.php',
        './resources/views/layouts/plain.blade.php',
        './resources/views/auth/**/*.blade.php',
        './resources/views/errors/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/seed/**/*.blade.php',
        './resources/views/admin/**/*.blade.php',
        './resources/views/notifications/**/*.blade.php',
        './resources/views/profile/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Support/**/*.php',
        './app/Models/Seed/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php',
    ],

    theme: {
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            white: '#FFFFFF',
            black: '#000000',

            // Raw ramps: neutral greys (zinc, no blue cast), the brand blue, and the
            // secondary hues used for charts, calls and tiles.
            gray: colors.zinc,
            blue: {
                50: '#EEF3FE', 100: '#DCE6FD', 200: '#BCD0FB', 300: '#8FB0F7', 400: '#5B8AF2',
                500: '#3B73EE', 600: '#2664EB', 700: '#1D4FC4', 800: '#1C419C', 900: '#1C397B', 950: '#0E1A3A',
            },
            sky: colors.sky,
            violet: colors.violet,
            pink: colors.pink,
            indigo: colors.indigo,
            // Emerald is the reviewer pages' colour (Review panel) and nothing else; Awarded uses the award token.
            emerald: colors.emerald,
            // Amber and red carry meaning only: waiting on someone, and stopped or late.
            // No green: an institutional rule.
            amber: colors.amber,
            red: colors.red,

            // Semantic tokens.
            background: token('background'),
            foreground: token('foreground'),
            card: { DEFAULT: token('card'), foreground: token('foreground') },
            muted: { DEFAULT: token('muted'), foreground: token('muted-foreground') },
            subtle: token('subtle'),
            border: token('border'),
            input: token('input'),
            ring: token('ring'),
            primary: { DEFAULT: token('primary'), foreground: token('primary-foreground'), soft: token('primary-soft') },
            sidebar: {
                DEFAULT: token('sidebar'), foreground: token('sidebar-foreground'),
                muted: token('sidebar-muted'), hover: token('sidebar-hover'), chip: token('sidebar-chip'),
            },
            ok: { DEFAULT: token('ok'), soft: token('ok-soft'), ink: token('ok-ink'), line: token('ok-line') },
            warn: { DEFAULT: token('warn'), soft: token('warn-soft'), ink: token('warn-ink'), line: token('warn-line') },
            danger: { DEFAULT: token('danger'), soft: token('danger-soft'), ink: token('danger-ink'), line: token('danger-line') },
            review: { DEFAULT: token('review'), soft: token('review-soft'), ink: token('review-ink'), line: token('review-line') },
            // Green exists for one use only: the Awarded status.
            award: { DEFAULT: token('award'), soft: token('award-soft'), ink: token('award-ink'), line: token('award-line') },
            neutral: { DEFAULT: token('neutral'), soft: token('neutral-soft'), ink: token('neutral-ink'), line: token('neutral-line') },
            // Each call's pale tint, set by .tint-1 … .tint-5 (SeedCall::tintClass()).
            tint: { DEFAULT: 'var(--tint-bg)', line: 'var(--tint-line)', chip: 'var(--tint-chip)', ink: 'var(--tint-ink)', solid: 'var(--tint-solid)' },
            chart: {
                1: token('chart-1'), 2: token('chart-2'), 3: token('chart-3'), 4: token('chart-4'), 5: token('chart-5'),
            },
        },

        extend: {
            fontFamily: {
                sans: ['Geist Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                mono: ['Geist Mono', 'ui-monospace', 'SFMono-Regular', 'Consolas', 'monospace'],
                // The grant agreement's text only, from the owner's Staff Pages design.
                serif: ['"Source Serif 4"', 'Georgia', 'serif'],
            },
            fontSize: {
                '2xs': ['0.6875rem', { lineHeight: '1rem' }],
            },
            borderRadius: {
                DEFAULT: '0.5rem',
                lg: '0.75rem',
                xl: '1rem',
            },
            boxShadow: {
                xs: '0 1px 2px 0 rgb(15 23 42 / 0.04)',
                sm: '0 1px 3px 0 rgb(15 23 42 / 0.06), 0 1px 2px -1px rgb(15 23 42 / 0.04)',
                pop: '0 12px 32px -8px rgb(15 23 42 / 0.18), 0 2px 6px -2px rgb(15 23 42 / 0.08)',
                // Cards that sit over a coloured band, and the white sign-in button.
                lift: '0 12px 32px -8px rgb(15 23 42 / 0.14)',
                brand: '0 18px 40px -16px rgb(14 26 58 / 0.55)',
                cta: '0 12px 28px -12px rgb(38 100 235 / 0.7)',
            },
            maxWidth: {
                page: '90rem',
            },
            keyframes: {
                'fade-in': { from: { opacity: 0, transform: 'translateY(-4px)' }, to: { opacity: 1, transform: 'none' } },
            },
            animation: {
                'fade-in': 'fade-in .15s ease-out',
            },
        },
    },

    plugins: [forms({ strategy: 'class' })],
};
