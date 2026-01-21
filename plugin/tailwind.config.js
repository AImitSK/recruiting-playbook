/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.php',
        './src/**/*.php',
        './assets/src/js/**/*.js',
    ],
    // rp- Prefix um Konflikte mit Themes zu vermeiden
    prefix: 'rp-',
    theme: {
        extend: {
            colors: {
                'rp-primary': '#2271b1',
                'rp-primary-dark': '#135e96',
                'rp-success': '#00a32a',
                'rp-warning': '#dba617',
                'rp-error': '#d63638',
            },
        },
    },
    plugins: [],
    // WordPress Admin Styles nicht überschreiben
    corePlugins: {
        preflight: false,
    },
};
