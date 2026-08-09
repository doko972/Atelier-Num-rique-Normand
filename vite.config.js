import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/*
 * Cible de compatibilité.
 *
 * Le public de ce site utilise souvent des appareils anciens : tablettes
 * bloquées sur une version d'iOS dépassée, téléphones Android de plusieurs
 * années. Sans cette cible explicite, la chaîne de compilation produit de la
 * syntaxe d'intervalle (`width >= 62rem`), comprise seulement à partir de
 * Safari 16.4 — sorti en 2023. Les navigateurs plus anciens ignoreraient
 * alors des règles entières de mise en page.
 */
const TARGETS = ['chrome87', 'edge88', 'firefox78', 'safari14', 'ios14'];

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'routes/**',
                'app/View/Components/**',
            ],
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
                quietDeps: true,
            },
        },
    },
    build: {
        target: TARGETS,
        cssTarget: TARGETS,
    },
    server: {
        /*
         * Écoute forcée en IPv4.
         *
         * Par défaut, Vite écoute sur `[::1]` et écrit cette adresse dans le
         * fichier `hot`. Or la grammaire d'une source CSP ne prévoit pas les
         * adresses IPv6 littérales entre crochets : le navigateur écarte
         * silencieusement `http://[::1]:5173`, puis bloque le script et les
         * styles. On ne peut donc pas régler le problème en élargissant la
         * politique — il faut que Vite parle en IPv4.
         */
        host: '127.0.0.1',

        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
