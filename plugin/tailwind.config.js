/** @type {import('tailwindcss').Config} */
module.exports = {
    /*
     * WICHTIG: Prefix für alle Tailwind-Klassen
     * Verhindert Konflikte mit WordPress-Themes
     */
    prefix: 'rp-',

    content: [
        './templates/**/*.php',
        './src/**/*.php',
        './assets/src/js/**/*.js',
    ],

    theme: {
        extend: {
            /*
             * Farben nutzen CSS Custom Properties
             * → Theme-Integration über --rp-color-* Variablen
             */
            colors: {
                // Primärfarbe - vom Theme/Admin überschreibbar
                primary: {
                    DEFAULT: 'var(--rp-color-primary)',
                    hover: 'var(--rp-color-primary-hover)',
                    light: 'var(--rp-color-primary-light)',
                    contrast: 'var(--rp-color-primary-contrast)',
                },
                // Status-Farben - fest
                success: {
                    DEFAULT: 'var(--rp-color-success)',
                    light: 'var(--rp-color-success-light)',
                },
                warning: {
                    DEFAULT: 'var(--rp-color-warning)',
                    light: 'var(--rp-color-warning-light)',
                },
                error: {
                    DEFAULT: 'var(--rp-color-error)',
                    light: 'var(--rp-color-error-light)',
                },
                info: {
                    DEFAULT: 'var(--rp-color-info)',
                    light: 'var(--rp-color-info-light)',
                },
                // Neutral-Farben
                surface: 'var(--rp-color-surface)',
                border: {
                    DEFAULT: 'var(--rp-color-border)',
                    dark: 'var(--rp-color-border-dark)',
                },
            },

            /*
             * Schriftgrößen - FEST vom Plugin kontrolliert
             * Nutzen CSS Custom Properties für Konsistenz
             */
            fontSize: {
                xs: ['var(--rp-text-xs)', { lineHeight: 'var(--rp-leading-normal)' }],
                sm: ['var(--rp-text-sm)', { lineHeight: 'var(--rp-leading-normal)' }],
                base: ['var(--rp-text-base)', { lineHeight: 'var(--rp-leading-normal)' }],
                lg: ['var(--rp-text-lg)', { lineHeight: 'var(--rp-leading-relaxed)' }],
                xl: ['var(--rp-text-xl)', { lineHeight: 'var(--rp-leading-relaxed)' }],
                '2xl': ['var(--rp-text-2xl)', { lineHeight: 'var(--rp-leading-tight)' }],
                '3xl': ['var(--rp-text-3xl)', { lineHeight: 'var(--rp-leading-tight)' }],
                '4xl': ['var(--rp-text-4xl)', { lineHeight: 'var(--rp-leading-none)' }],
                '5xl': ['var(--rp-text-5xl)', { lineHeight: 'var(--rp-leading-none)' }],
            },

            /*
             * Spacing - FEST vom Plugin kontrolliert
             */
            spacing: {
                '0': 'var(--rp-space-0)',
                '1': 'var(--rp-space-1)',
                '2': 'var(--rp-space-2)',
                '3': 'var(--rp-space-3)',
                '4': 'var(--rp-space-4)',
                '5': 'var(--rp-space-5)',
                '6': 'var(--rp-space-6)',
                '8': 'var(--rp-space-8)',
                '10': 'var(--rp-space-10)',
                '12': 'var(--rp-space-12)',
                '16': 'var(--rp-space-16)',
                '20': 'var(--rp-space-20)',
                '24': 'var(--rp-space-24)',
            },

            /*
             * Border Radius - FEST vom Plugin kontrolliert
             */
            borderRadius: {
                none: 'var(--rp-radius-none)',
                sm: 'var(--rp-radius-sm)',
                DEFAULT: 'var(--rp-radius)',
                md: 'var(--rp-radius-md)',
                lg: 'var(--rp-radius-lg)',
                xl: 'var(--rp-radius-xl)',
                '2xl': 'var(--rp-radius-2xl)',
                full: 'var(--rp-radius-full)',
            },

            /*
             * Shadows - FEST vom Plugin kontrolliert
             */
            boxShadow: {
                sm: 'var(--rp-shadow-sm)',
                DEFAULT: 'var(--rp-shadow)',
                md: 'var(--rp-shadow-md)',
                lg: 'var(--rp-shadow-lg)',
                xl: 'var(--rp-shadow-xl)',
                none: 'none',
            },

            /*
             * Transitions
             */
            transitionDuration: {
                fast: '150ms',
                DEFAULT: '200ms',
                slow: '300ms',
            },

            /*
             * Z-Index Scale
             */
            zIndex: {
                dropdown: 'var(--rp-z-dropdown)',
                modal: 'var(--rp-z-modal)',
                tooltip: 'var(--rp-z-tooltip)',
            },
        },
    },

    plugins: [],

    /*
     * Preflight (CSS Reset) ist DEAKTIVIERT
     * → Würde Theme-Styles zerstören
     * → Wir haben unseren eigenen scoped Reset in main.css
     */
    corePlugins: {
        preflight: false,
    },
};
