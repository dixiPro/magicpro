import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    server: {
        host: "192.168.1.33", // ваш IP
        port: 5173,
        cors: true,
        hmr: { host: "192.168.1.33", port: 5173, protocol: "http" },

        watch: {
            // Явно игнорируем тяжёлые/шумные директории и файлы
            ignored: [
                "**magicPro/config**",
                "**magicPro/data**",
                "**magicPro/database**",
                "**magicPro/resources**",
                "**/app/**",
                "**/bootstrap/**",
                "**/config/**",
                "**/database/**",
                "**/lang/**",
                "**/node_modules/**",
                "**/public/**",
                "**/resources/**",
                "**/routes/**",
                "**/storage/**",
                "**/tempFiles/**",
                "**/tests/**",
                "**/vendor/**",
            ],
        },
    },

    plugins: [
        vue(),
        laravel({
            input: [
                "magicPro/admin/js/editUsers/editUsers.js",
                "magicPro/admin/js/artEditor.js",
                "magicPro/admin/css/admin.css",
                "magicPro/admin/js/adminCommon.js",
                "magicPro/admin/js/adminCommon.js",

                "resources/css/app.css",
                "resources/js/app.js",
            ],
            refresh: true,
        }),
    ],
});
