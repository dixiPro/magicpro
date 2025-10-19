import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    base: "/vendor/magicpro/", // <<< добавь это
    plugins: [
        vue(),
        laravel({
            input: [
                "admin/js/editUsers/editUsers.js",
                "admin/js/artEditor.js",
                "admin/css/admin.css",
                "admin/js/adminCommon.js",
            ],
            refresh: true,
            hotFile: path.resolve(
                __dirname,
                "../../../storage/magicpro.vite.hot"
            ),
        }),
    ],

    build: {
        outDir: path.resolve(__dirname, "../../../public/vendor/magicpro"), // в общую public
        emptyOutDir: true,
        // manifest: ".vite/manifest.json", // ← вместо true
    },

    server: {
        host: true,
        port: 5174,
        cors: true,
        hmr: {
            host: "localhost",
            port: 5174,
        },
    },
});
