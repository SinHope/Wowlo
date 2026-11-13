import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/Linkify.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            // Wowlo brand palette — warm, encouraging, easy on the eyes.
            colors: {
                primary: {
                    DEFAULT: '#7C3AED', // warm violet — buttons, links, active nav
                    dark: '#6D28D9',    // for violet text on light backgrounds (4.5:1+)
                    light: '#A78BFA',
                },
                cream: 'rgb(var(--c-cream) / <alpha-value>)', // page background (#FFFBF5 light), themed via CSS vars in app.css
                accent: {
                    DEFAULT: '#F59E0B', // amber — achievement, badges, highlights
                    dark: '#D97706',    // amber text
                },
                // Views use bare `bg-amber` / `bg-amber/10` (badges, hint boxes); Tailwind's amber
                // has no DEFAULT shade, so those rendered nothing. Merges with amber-50..950.
                amber: { DEFAULT: '#F59E0B' },
                success: '#16A34A',     // homework done / payment confirmed
                danger: '#DC2626',      // error states only
                ink: 'rgb(var(--c-ink) / <alpha-value>)',     // body + headings (#2A2235 light)
                muted: 'rgb(var(--c-muted) / <alpha-value>)', // captions (#6B5E73 light, 4.5:1 on cream)
            },
        },
    },

    plugins: [forms],
};
