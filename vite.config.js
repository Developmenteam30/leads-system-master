import {defineConfig, loadEnv} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'
import basicSsl from '@vitejs/plugin-basic-ssl'

export default defineConfig(({command, mode}) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        // The Vue plugin will re-write asset URLs, when referenced
                        // in Single File Components, to point to the Laravel web
                        // server. Setting this to `null` allows the Laravel plugin
                        // to instead re-write asset URLs to point to the Vite
                        // server instead.
                        base: null,

                        // The Vue plugin will parse absolute URLs and treat them
                        // as absolute paths to files on disk. Setting this to
                        // `false` will leave absolute URLs un-touched so they can
                        // reference assets in the public directory as expected.
                        includeAbsolute: false,
                    },
                },
            }),
            env.APP_VITE_SERVE_HTTPS === 'true' ? basicSsl() : null,
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, './resources/js'),
                vue: "vue/dist/vue.esm-bundler.js",
            },
        },
        server: {
            https: env.APP_VITE_SERVE_HTTPS === 'true',
            host: env.APP_VITE_HOST,
            port: env.APP_VITE_PORT,
            hmr: {
                host: env.APP_VITE_HOST,
                protocol: env.APP_VITE_PROXY_HTTPS === 'true' ? 'wss' : 'ws',
            },
        }
    }
});
