import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],
    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--font)'],
                mono: ['var(--font-mono)'],
            },
            colors: {
                // Paleta TEAL calibrada em torno de #1ABB9C (Gentelella v4 brand).
                // Manteve-se o nome 'indigo' para nao precisar refatorar 218
                // ocorrencias em 40 arquivos JSX — apenas os valores HEX foram
                // trocados. Para reverter o sistema todo para azul, basta restaurar
                // o bloco anterior deste mesmo arquivo (git log).
                indigo: {
                    50:  '#E6F8F4',
                    100: '#C9F0E5',
                    200: '#94E2CC',
                    300: '#5DCAA5',
                    400: '#3CC4A8',
                    500: '#1ABB9C', // primary (Gentelella --primary)
                    600: '#169F85', // dark/hover  (Gentelella --primary-dk)
                    700: '#128270',
                    800: '#0F6E56',
                    900: '#0A4E3F',
                    950: '#052F26',
                },
                slate: {
                    850: '#151e2e',
                    900: '#0f172a',
                },
                gentelella: {
                    sidebar: '#2A3F54',
                    sidebarHover: '#233446',
                    bg: '#F7F7F7',
                    border: '#E6E9ED',
                    text: '#73879C'
                }
            },
            boxShadow: {
                'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                'glass': '0 8px 32px 0 rgba(0, 0, 0, 0.05)',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                }
            }
        },
    },

    plugins: [forms],
};
