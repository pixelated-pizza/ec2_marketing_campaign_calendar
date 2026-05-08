import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import { fileURLToPath, URL } from "url";
import os from "os";

function getLanIp() {
    const interfaces = os.networkInterfaces();
    for (const name of Object.keys(interfaces)) {
        for (const iface of interfaces[name]) {
            if (iface.family === "IPv4" && !iface.internal) {
                return iface.address;
            }
        }
    }
    return "localhost"; // fallback
}

const lanIp = getLanIp();

export default defineConfig({
    base: env.VITE_BASE_URL ? env.VITE_BASE_URL + "/" : "/",
    server: {
        host: "0.0.0.0",      // bind to all network interfaces
        cors: true,  
        port: 5173,
        hmr: {
            host: "192.168.22.121",  // your LAN IP
        },
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
            assetUrl: env.VITE_ASSET_URL || "",
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
        dedupe: ["@fullcalendar/core"],
    },
    build: {
    outDir: "public/build",
    emptyOutDir: true,
    chunkSizeWarningLimit: 1500,
    cssMinify: false,
},
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: ["resources/css/assets/variables"],
            },
        },
    },
});
