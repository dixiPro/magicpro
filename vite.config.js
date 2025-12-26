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
        'admin/js/artEditor.js', //
        'admin/js/editUsers.js',
        'admin/js/crawler.js',
        'admin/js/setup.js',
      ],
      refresh: true,
      hotFile: path.resolve(__dirname, '../../../storage/magicpro.vite.hot'),
      publicDirectory: '../../../public',
      buildDirectory: 'vendor/magicpro', // <— манифест будет: public/vendor/magicpro/manifest.json
    }),

    {
      name: 'clean-assets-before-build',
      // вызывается ДО сборки Vite
      // удаляет старые assets, чтобы не было мусора и конфликтов
      buildStart() {
        console.log('--> Build start');
        const assetsDir = '../../../public/vendor/magicpro/assets';
        if (fs.existsSync(assetsDir)) {
          fs.removeSync(assetsDir);
          console.log('🧹 old assets cleaned');
        }
      },
    },
    {
      name: 'copy-after-build',
      closeBundle() {
        console.log('✅ start copying');

        // сгенерированные файлы вайтом
        // сохраняются в паблик проекта.
        const buildDirectory = '../../../public/vendor/magicpro/';

        // сюда сохраняем сегенеренное для хранения в пакете
        const readyBundle = 'readyBundle/';

        fs.emptyDirSync(readyBundle + 'assets'); // 🔥 очищает только папку assests
        // что копируем, куда копируем
        fs.copySync(
          buildDirectory + 'assets', //
          readyBundle + 'assets'
        );

        fs.copySync(
          buildDirectory + 'manifest.json', //
          readyBundle + 'manifest.json'
        );

        // // проверяем папку, тут лежат все файлы
        // // которые подключаются в хеадере
        // const externalInPublic = '../../../public/vendor/magicpro/external/';
        // const packageExternal = 'public/vendor/magicpro/external/';

        // if (!fs.existsSync(externalInPublic)) {
        //   console.log('Папка существует');
        // }

        console.log('✅ MagicPro assets copied readyBundle');
      },
    },
  ],

  // build: {
  //   sourcemap: true,
  // },

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

// npm install bootstrap sass @fullhuman/postcss-purgecss --save-dev
// npm uninstall sass --save-dev
// npm install sass@1.71.1 --save-dev
// npm install @fortawesome/fontawesome-free --save-dev
