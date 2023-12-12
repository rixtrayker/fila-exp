import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
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
            }
        }
    },
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
