import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs-extra';

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: [
        'admin/js/artEditor.js',
        //
        'admin/js/editUsers.js',
      ],
      refresh: true,
      hotFile: path.resolve(__dirname, '../../../storage/magicpro.vite.hot'),
      publicDirectory: '../../../public',
      buildDirectory: 'vendor/magicpro', // <— манифест будет: public/vendor/magicpro/manifest.json
    }),
    {
      name: 'copy-after-build',
      closeBundle() {
        console.log('✅ start copying');

        const buildDirectory = '../../../public/vendor/magicpro/';
        const publicDirectory = 'public/vendor/magicpro/';

        fs.emptyDirSync(publicDirectory); // 🔥 очищает папку
        // что копируем, куда копируем
        fs.copySync(
          buildDirectory + 'assets', //
          publicDirectory + 'assets'
        );

        fs.copySync(
          buildDirectory + 'manifest.json', //
          publicDirectory + 'manifest.json'
        );

        console.log('✅ MagicPro assets copied to public/vendor/magicpro');
      },
    },
  ],
  server: {
    // host: "127.0.0.1", // фиксируем IPv4
    // port: 5174,
    strictPort: true,
    fs: { allow: [path.resolve(__dirname, '../../..')] },
    watch: {
      ignored: [
        '**/admin/controller**', //
        '**/admin/middleware/**',
        '**/admin/views/**',
        '**/data/**',
        '**/database/**',
        '**/public/**',
        '**/src/**',
      ],
    },
  },
});
