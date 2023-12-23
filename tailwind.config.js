import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                chartblue: "#DAECFC",
                chartgreen: "#DAF5F5",
                chartred: "#FFE1E6",
                chartblueB: "#35A2EB",
                chartgreenB: "#21CFCF",
                chartredB: "#FF4069",
            },
        },
    },

    plugins: [forms, typography],
};
