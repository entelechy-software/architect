/** @type {import('tailwindcss').Config} */
module.exports = {
    // Dark mode toggled via data-theme attribute set by the host app or docs site.
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        // Tailwind scans these for @apply directives only.
        // The compiled output CSS is consumed via arch-* semantic classes.
        // External consumers do not need Tailwind installed.
        './src/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/css/architect.css',
    ],
    theme: {
        extend: {
            colors: {
                'arch': {
                    50:  '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
