const colors = require('tailwindcss/colors')
const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    // darkMode: 'class',
    theme: {
        extend: {
            colors: {
                danger: colors.rose,
                success: colors.green,
                chartblue: '#DAECFC',
                chartgreen: '#DAF5F5',
                chartred: '#FFE1E6',
                chartblueB: '#35A2EB',
                chartgreenB: '#21CFCF',
                chartredB: '#FF4069',
            },
            fontFamily: {
                // sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
