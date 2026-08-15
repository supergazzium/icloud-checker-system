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
                // Latin + Thai in one stack. Noto Sans and Noto Sans Thai
                // Looped are designed as siblings — same weights, same
                // proportions — so bilingual UIs stay visually consistent.
                sans: ['"Noto Sans"', '"Noto Sans Thai Looped"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
