# ТЗ v2: MagicImage — отдельный компонент

## Назначение

- Компонент должен сам читать из файла
- Компонент должен сам читать из буфера обмена
- Компонент должен отдавать блоб или png(средствами браузера) или jpg (средствами браузера) или png(средствами браузера), а кто его вызывал уже пусть сам думает, что с этим делать и в какой формат сохранять.
- Кропер можно нарисовать самим, что бы не зависить от других пакетов.
- Локализации нет все сообщения в файле. нужно правь руками
- После добавим туда иконки (отдельным файлом)
- Т.е. компонент максимально независим.

## Компонент живет внутри magicPro

admin/js/magic-image/
├── src/
│ ├── MagicImage.vue сам компонент
│ └── assets/
│ └── icons/ иконки компонента
├── App.vue песочница
├── main.js вход песочницы
├── index.html  
├── package.json
├── vite.config.js отдельный build MagicImage
└── README.md пропсы, события, примеры

Сборка MagicImage: результат складываем `public/magic-image/` для тестирования на боевом маджик про этого не будет.

## vite.config.js примерный

```js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      vue: resolve(__dirname, '../../../node_modules/vue'),
    },
    dedupe: ['vue'],
  },
  build: {
    outDir: '../../../../../../public/magic-image',
    emptyOutDir: true,
  },
});
```

## package.json примерно

```json
{
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^6.0.1",
    "vite": "^7.0.4"
  },
  "dependencies": {
    "vue": "^3.5.21",
    "vue-advanced-cropper": "^2.8.9"
  }
}
```

MagicPro при сборке берет MagicImage.vue прямо из: `admin/js/magic-image/src/MagicImage.vue`

## Итого

- MagicPro — свой build
- MagicImage — свой build
- готовый MagicImage лежит в public/magic-image/-
- папку admin/js/magic-image вынесу в отдельный GitHub-репозиторий
- в магазин уедет пака magic-image/src и он его там сам подключит

##

Основа — `imageEditor_notes.md`. Отличие: компонент выносится в **отдельный
репозиторий** и подключается к MagicPro как зависимость, а не копируется.

## Решено

1. Редактор живёт своим репозиторием на GitHub, внутри — песочница для проверки.
2. MagicPro подключает его как пакет и пишет вокруг него две обёртки: ленты и
   файловый менеджер.
3. Про сервер компонент не знает: отдаёт готовый результат, отправляет хозяин.

## Раскладка репозитория

```text
magic-image/
├── src/
│   └── MagicImage.vue     сам компонент
├── App.vue                песочница: подёргать руками
├── main.js                точка входа песочницы
├── index.html
├── package.json
├── vite.config.js         два режима: dev-песочница и сборка библиотеки
└── README.md              вызов, пропсы, события, примеры
```

- `name`: `@dixipro/magic-image`.
- `peerDependencies`: `vue`, `vue-advanced-cropper` — версии берёт хозяин, двух
  копий Vue в сборке быть не должно.
- Сборка: `vite build --mode lib` → `dist/magic-image.es.js` + css.
- Песочница: `npm run dev` открывает `index.html` с `App.vue`.

## Как вызывается

```vue
<script setup>
import { ref } from 'vue';
import MagicImage from '@dixipro/magic-image';
import '@dixipro/magic-image/style.css';

const source = ref(null); // File | null
const editor = ref(null);

async function onSave(result) {
  // result.file — готовый File, его и отправляем
}
</script>

<template>
  <MagicImage ref="editor" v-model="source" :min-x="600" :min-y="600" ratio="16/9" format="image/png" lang="ru" @save="onSave">
    <template #actions="{ canSave, apply }">
      <button v-if="canSave" @click="apply()">Сохранить</button>
    </template>
  </MagicImage>
</template>
```

## Модель

`v-model` — **исходный файл**, `File` или `null`.

- Хозяин может положить туда файл сам: перетаскивание, буфер, «взять прежнюю
  картинку».
- Компонент пишет в модель, когда файл выбрали в нём; `null` — сброс, экран
  чистый.
- Результат моделью не отдаётся: он не всегда есть и не всегда нужен, для него
  событие `save`.

## Пропсы

| Проп           | Тип      | Умолчание                             | Что делает                                                                    |
| -------------- | -------- | ------------------------------------- | ----------------------------------------------------------------------------- |
| `minX`, `minY` | number   | `0`                                   | меньше — сохранять нельзя, кнопка гаснет                                      |
| `ratio`        | string   | `''`                                  | жёсткая пропорция кропа, `16/9`; пусто — свободная                            |
| `ratios`       | string[] | `[]`                                  | список пропорций на выбор; пусто — выбора нет                                 |
| `format`       | string   | `image/webp`                          | формат результата: `image/webp`, `image/png`, `image/jpeg`                    |
| `quality`      | number   | `0.95`                                | качество кодирования                                                          |
| `maxHeight`    | number   | `0`                                   | потолок превью по высоте; `0` — меряет по месту                               |
| `lang`         | string   | `ru`                                  | язык подписей, словарь внутри компонента                                      |
| `tools`        | string[] | `['light','levels','sharpen','crop']` | какие вкладки показывать                                                      |
| `pasteTarget`  | string   | `'self'`                              | откуда ловить `Ctrl-V`: `self` — пока компонент на экране, `none` — не ловить |

## События

| Событие             | Что приносит                                          |
| ------------------- | ----------------------------------------------------- |
| `update:modelValue` | выбранный `File` или `null`                           |
| `loaded`            | `{ width, height, name }` — исходник прочитан         |
| `save`              | `{ file, blob, canvas, width, height, name, format }` |
| `clear`             | редактор очищен                                       |

`file` — готовый `File` с именем `name` и расширением по `format`. `dataURL`
больше не отдаётся: base64 на треть тяжелее, и нужен он не всем — кому нужен,
сделает из `blob`.

## Методы (`defineExpose`)

`apply()` — собрать результат и выдать `save`; `load(file)` — загрузить файл
снаружи; `clear()` — очистить.

## Слот

`#actions="{ canSave, apply }"` — кнопки хозяина: «Сохранить», «Отмена», свои.

## Что меняется против нынешнего MagicImage

- формат и качество — пропсы, а не константы внутри;
- в `save` едет `File`, а не `dataURL`;
- `paste` слушается, пока компонент на экране, а не всегда;
- пропорции кропа приходят снаружи, список внутри не зашит;
- вкладки можно выключать.

## Обёртки в MagicPro

- **Лента** — нынешний `dataImageUpload.vue`: диалог, имя файла, `alt`,
  `minWidth` и `ratio` из схемы, отправка `imageUpload` / `imageCropUpload`.
  Свой кроп заменяет на `MagicImage`, `format` берёт из `uploadFormat`.
- **Файловый менеджер** — новая обёртка: диалог, имя файла, текущая папка,
  отправка в `/a_dmin/api/fileManager`.

## Вопросы

1. **Как подключаем:** публикуем в npm или ставим прямо из GitHub
   (`github:dixipro/magic-image#v1.0.0`)? Во втором случае в репозитории должен
   лежать собранный `dist`.
2. **Кто хозяин копии в магазине:** магазин переходит на пакет или пока живёт
   со своей копией?
   > магазин потом перейдет сам
3. **Формат для лент:** png/jpeg с перекодировкой на сервере, как сейчас, или
   webp из браузера как есть?
   > компонент главный
4. **Совпадение имени** файла в папке файлового менеджера: спрашивать, затирать,
   дописывать суффикс?
   > компоненту должно быть пофиг имя
5. **Предел размера** исходника: уменьшать перед обработкой или отказывать?
