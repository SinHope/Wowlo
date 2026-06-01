import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
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
                cream: '#FFFBF5',       // warm off-white page background
                accent: {
                    DEFAULT: '#F59E0B', // amber — achievement, badges, highlights
                    dark: '#D97706',    // amber text
                },
                success: '#16A34A',     // homework done / payment confirmed
                danger: '#DC2626',      // error states only
                ink: '#2A2235',         // warm charcoal — body + headings
                muted: '#6B5E73',       // captions (still 4.5:1 on cream)
            },
        },
    },

    plugins: [forms],
};
