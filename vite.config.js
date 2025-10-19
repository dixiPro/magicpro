import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                "admin/js/artEditor.js",
                //
                "admin/js/editUsers.js",
            ],
            refresh: true,
            hotFile: path.resolve(
                __dirname,
                "../../../storage/magicpro.vite.hot",
            ),
            publicDirectory: "../../../public",
            buildDirectory: "vendor/magicpro", // <— манифест будет: public/vendor/magicpro/manifest.json
        }),
    ],
    server: {
        // host: "127.0.0.1", // фиксируем IPv4
        // port: 5174,
        strictPort: true,
        fs: { allow: [path.resolve(__dirname, "../../..")] },
    },
});
