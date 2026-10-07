
import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import { fileURLToPath, URL } from "url";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    return {
        base: "/markmap/",

        server: {
            host: "0.0.0.0",
            port: 5173,
            strictPort: true,
            cors: true,
            origin: env.VITE_APP_URL
                ? `${env.VITE_APP_URL.replace(/:\d+$/, "")}:5173`
                : undefined,
        },

        plugins: [
            laravel({
                input: [
                    "resources/css/app.css",
                    "resources/css/mainlayout.css",
                    "resources/css/dashboard.css",
                    "resources/js/app.js",
                    "resources/css/campaigns.css",
                    "resources/css/wsd.css",
                    "resources/css/campaign_chart.css",
                    "resources/css/internal_promos.css",
                    "resources/css/external_promos.css",
                    "resources/css/website_campaigns.css",
                ],
                refresh: true,
            }),

            tailwindcss(),
            vue(),

            Components({
                resolvers: [PrimeVueResolver()],
            }),
        ],

        resolve: {
            alias: {
                "@": fileURLToPath(new URL("./resources", import.meta.url)),
                "@/js": fileURLToPath(new URL("./resources/js", import.meta.url)),
                "@public": fileURLToPath(new URL("./public", import.meta.url)),
                vue: "vue/dist/vue.esm-bundler.js",
            },
        },

        build: {
            outDir: "public/build",
            emptyOutDir: true,
            chunkSizeWarningLimit: 1500,
        },
    };
});