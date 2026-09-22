# MagicImage — Vue 3 clipboard/file input, crop, levels, brightness, contrast, saturation, sharpening and PNG/JPEG/WebP export

There are plenty of fine packages for cropping and processing images. Around
almost every one of them, though, the same code gets written again and again:
taking a file from disk, pasting from the clipboard, the control buttons,
showing and hiding the right things at the right moment, catching the result and
doing something with it.

MagicImage puts all of that into a single Vue 3 component — a sort of Swiss army
knife for working with pictures.

The component takes a picture from a file or from the clipboard, lets it be
cropped and adjusted, and gives back two files:

- the original picture;
- the processed picture in the chosen format.

The point of MagicImage is not to replace the specialised image libraries, but
to gather the whole everyday scenario of working with a picture into one ready
component.

It knows nothing about servers: the result leaves with a `save` event, and what
to do with it is up to the owner.

- no network, no uploads, no file names asked;
- no picture given back without a crop;
- one dependency of its own: [vue-advanced-cropper](https://github.com/Norserium/vue-advanced-cropper).

## Contents

- [How it works](#how-it-works)
- [Install](#install)
- [Usage](#usage)
- [Props](#props)
- [Tuning](#tuning)
- [Crop modes](#crop-modes)
- [Resize down](#resize-down)
- [Continue and the status line](#continue-and-the-status-line)
- [Events](#events)
- [Slot](#slot)
- [Errors](#errors)
- [File names](#file-names)
- [The clipboard](#the-clipboard)
- [Inside](#inside)
- [Development](#development)

## How it works

1. **Upload** — a file from disk, or the paste field and Ctrl-V.
2. **Tune** — brightness, contrast, saturation; levels with a histogram;
   sharpness. Each tab is switched on by its own «Apply»; nothing is applied
   until it is.
3. **Crop** — the stencil. It is mandatory. The final size can be set by hand
   with «Resize down».
4. **Continue** — appears once everything is in order; it fires the `save`
   event, after which the component clears itself.

A picture bigger than `maxPixels` is shrunk at load: the edits and the stencil
work on the smaller copy. The original is never touched — it leaves as it came.

## Install

```bash
npm i @dixipro/magic-image
```

**Not published yet.** The command above is what it will be; until the first
release, copy the `src/` folder into your project and import by a relative path:

```js
import MagicImage from './magic-image/src/MagicImage.vue';
```

`vue` and `vue-advanced-cropper` are peer dependencies: the component uses the
copies your project already has. The package ships the source, so your bundler
compiles the `.vue` file — Vite or Webpack with the Vue plugin, which any Vue
project has anyway. The styles come with the component, nothing extra to import.

Copying the folder into a project works too; then the import is a relative path
instead of the package name.

## Usage

```vue
<script setup>
import { ref } from 'vue';
import MagicImage from '@dixipro/magic-image';

const busy = ref(false);

function onSave({ original, crop }) {
  // original.file, crop.file — ready File objects
}
</script>

<template>
  <div v-if="!busy">…anything that should step aside while the editor works…</div>

  <MagicImage
    :max-pixels="16000000"
    output-format="png"
    :output-quality="100"
    clipboard-format="png"
    :clipboard-quality="100"
    crop-mode="fixed"
    :crop-x="1200"
    :crop-y="675"
    @stage="busy = $event !== 'empty'"
    @save="onSave"
  >
    <template #actions="{ canSave, apply }">
      <button v-if="canSave" @click="apply()">Continue</button>
    </template>
  </MagicImage>
</template>
```

The minimal call is the handler alone, everything else by default:

```vue
<MagicImage @save="onSave" />
```

## Props

| Prop               | Type   | Default    | What it does                                    |
| ------------------ | ------ | ---------- | ----------------------------------------------- |
| `maxPixels`        | number | `16000000` | anything bigger is shrunk at load               |
| `outputFormat`     | string | `'png'`    | crop format: `png`, `jpg`, `webp`               |
| `outputQuality`    | number | `100`      | crop quality, 1–100                             |
| `clipboardFormat`  | string | `'png'`    | format of an original coming from the clipboard |
| `clipboardQuality` | number | `100`      | clipboard quality, 1–100                        |
| `cropMode`         | string | `'any'`    | `any`, `fixed`, `min`                           |
| `cropRatio`        | string | `''`       | stencil proportion: `1`, `1.5` or `16/9`        |
| `cropX`            | number | not set    | required width                                  |
| `cropY`            | number | not set    | required height                                 |

Formats are written short; the component builds the mime type itself. Numbers
are passed with a colon: `:crop-x="1200"`.

**PNG has no quality:** it is lossless, the quality props do not touch it.
**JPG has no transparency:** a PNG with an alpha channel comes out on a black
background, no white underlay is added.

## Tuning

Three tabs: **Light** (brightness, contrast, saturation), **Levels** (black
point, gamma, white point, over a histogram of the source) and **Sharpen**
(amount, radius, threshold).

- **Apply.** Every tab has its own checkbox, and all three start switched off.
  Until a tab is switched on, its sliders are greyed out and it reaches neither
  the preview nor the file — what is applied is exactly what is ticked. Switched
  off again, the tab keeps its values on the sliders, ready for the next time.
- **Slider and number.** Each slider has a number field next to it, and the two
  move together. A typed number goes in once it is complete and inside the
  slider range, so decimals can be typed without the dot vanishing under the
  cursor; on Enter or blur a number outside the range is brought to its edge.
- **Reset** brings the open tab back to neutral values and leaves its «Apply»
  as it was: reset is about values, not about use.
- **Sharpen** carries a short hint under «Reset» on what each of its three
  settings does.

## Crop modes

### any

`cropX` and `cropY` are not looked at. With `cropRatio` the stencil holds the
proportion, without it the stencil is free. Every picture is accepted, the crop
leaves at the size of the stencil. The «Resize down» checkbox is there, with no
lower bound.

### fixed

The crop is **always** scaled to exactly `cropX` by the computed height, no
matter what stencil the person dragged. Nothing is enlarged: the save button
appears only once the stencil is at least that wide. The «Resize down» checkbox
is not shown — the size comes from the props.

How the props are brought to one shape:

| n   | cropRatio | cropX | cropY | what happens                                 |
| --- | --------- | ----- | ----- | -------------------------------------------- |
| 1   | +         | +     | −     | the main case                                |
| 2   | +         | +     | +     | `cropY` is dropped                           |
| 3   | −         | +     | +     | `cropRatio` = x / y, then `cropY` is dropped |
| 4   | +         | −     | +     | x = y × ratio                                |
| 5   | +         | −     | −     | error, invalid parameters                    |

Everything ends up as case 1: a proportion plus X. Any other combination — no
proportion and only one side, or neither side — is an error.

### min

Like `fixed`, but the result is never squeezed: a stencil bigger than required
leaves as it is, a smaller one is refused. The «Resize down» checkbox is there
but will not go below the required size.

## Resize down

In the crop stage «Resize down» sets the final size by hand. It is there in
`any` (no lower bound) and in `min` (the required size is the lower bound);
`fixed` has no use for it, the size comes from the props.

- Type one side — the other follows the stencil proportion at once.
- The fields never correct anybody: whatever is typed stays. Whether the numbers
  are any good decides «Continue» — see below.
- Moving the stencil switches «Resize down» off and puts the stencil size back
  into the fields: a size typed for the old stencil means nothing for the new
  one. Moving the stencil without changing its size keeps the numbers.

## Continue and the status line

«Continue» is not greyed out — it is simply not there until everything is in
order. Above the buttons a status line says which:

- **✓ Ready to save** — the stencil holds what the mode requires, and the
  «Resize down» numbers, if used, are within bounds;
- **✕ The stencil is smaller than required** — `fixed` or `min`, and the
  stencil is narrower or lower than `cropX` × the computed height;
- **✕ The final size is out of range** — the «Resize down» numbers are empty,
  bigger than the stencil, or in `min` below the required size.

While the stencil is still loading the picture there is no status and no
button: the size of the stencil comes from the cropper itself, and until it
reports there is nothing to check.

## Events

### `save`

```js
{
  original: { file, width, height, format },
  crop:     { file, width, height, format },
}
```

- `original` — the source. A file from disk leaves as it came, with its own
  bytes and type; a clipboard picture is handed over as `clipboardFormat`.
- `crop` — cropped and processed, in `outputFormat`.
- Both parts are always there, and the component clears itself right after.

### `stage`

`'empty' | 'edit' | 'crop'` — fires on every move. `empty` means the component
is free: nothing is taken, and whatever the page hid can come back.

```html
<MagicImage @stage="busy = $event !== 'empty'" @save="onSave" />
```

## Slot

`#actions="{ canSave, apply }"` — the owner's button, named however they like.
Without the slot the component draws its own «Continue», shown only while
`canSave` is true. `canSave` means everything the status line checks: the
stencil holds what the mode requires, and the «Resize down» numbers are good.
The status line stays either way.

## Errors

An error is shown inside the component instead of the buttons:

- **invalid parameters** — see the table above; the component does not work at
  all until the props are fixed, and it will not even take a picture;
- **the required size is out of reach** — in `fixed` and `min`, when the working
  copy is narrower than `cropX`. This covers a picture that is simply too small
  and a picture that `maxPixels` had to shrink;
- **this file could not be read as a picture** — a broken or unsupported file;
- **the browser could not encode the picture** — `canvas.toBlob()` gave nothing
  back, which happens on canvases past the browser's limits.

The crop is taken from the shrunk copy, never from the full-size original, so
`cropX` has to be within reach of the given `maxPixels`.

## File names

| Source          | `original`                    | `crop`                     |
| --------------- | ----------------------------- | -------------------------- |
| file `logo.jpg` | `logo.jpg`                    | `logo.<outputFormat>`      |
| clipboard       | `clipBoard.<clipboardFormat>` | `clipBoard.<outputFormat>` |

The two names can match (`logo.png` with `output-format="png"`). That is fine:
the parts arrive as separate properties of the object, and what to do about the
names is the owner's call.

## The clipboard

Paste goes into the field next to «Upload», not into the window: what is pasted
into someone else's input is none of the component's business. The field is
there only while nothing is taken, so an open picture cannot be overwritten by
a stray Ctrl-V.

The clipboard hands over an already encoded picture, almost always PNG. When its
type is the one `clipboardFormat` asks for, **nothing is re-encoded** — that blob
becomes the original and no canvas is built. A canvas appears only when the
format really has to change.

## Inside

```text
src/
  MagicImage.vue    stages, the incoming picture, the preview, the save event
  TunePanel.vue     the three tuning tabs over one model, plus the histogram
  CropStage.vue     the stencil, the sizes and the «Resize down» fields
  lib/plan.js       formats, the proportion and the crop modes — plain functions
  lib/pixels.js     levels, sharpening, shrinking, the histogram
  lib/text.js       every word the component says
  assets/style.css  buttons, tabs, fields — Bootstrap-like, own classes, mi- prefix
test/
  plan.test.js      the table of crop modes, case by case

App.vue             the demo page: the state and which part is on screen
DemoParams.vue      the cases, the parameter fields and the «Go» button
ShowResult.vue      the two files of the last save, with download buttons
InstallMagicImage.vue  the install command and the call for the chosen parameters
demoProps.js        what the demo knows about the props: defaults and cases
```

**MagicImage.vue** is the orchestrator and holds no arithmetic of its own. It
keeps the stage (`empty` / `edit` / `crop`), takes the file or the paste, builds
the working copy, draws the preview, and turns the crop into the two files of
`save`.

Two things in it are worth knowing about:

- **A session counter.** Decoding and encoding are slow. Every take, every
  cancel and the unmount raise the counter; an operation carries its number and
  drops whatever it produced if the number has moved. Without it a late `await`
  could drop picture A on top of picture B, or bring a cancelled crop back.
- **Everything for `save` is taken before the wait.** A cancel in the middle of
  encoding cannot turn into half of one picture and half of another.

**TunePanel.vue** takes `{ light, levels, sharpen }` as a `v-model` and never
mutates it: a new object leaves on every move. Each part carries `on` — the
«Apply» checkbox of its tab. The panel also draws the histogram, which belongs
to the «Levels» tab.

**CropStage.vue** owns the stencil and the final size. It reports the size of
the stencil with `change`, whether the «Resize down» numbers are usable with
`valid`, and gives the ready canvas from `getCanvas()`, which returns
`{ canvas }` or `{ error }` — the limits are checked against the canvas in hand,
not against the numbers the interface managed to show.

**lib/plan.js** is the only place where the modes live, and the only file fully
covered by tests. **lib/pixels.js** holds the pixel work; the settings come as
arguments, nothing there reads the state of a component. A part switched off
by «Apply» counts as untouched there, so it is skipped in the preview and in the
processed copy alike.

Light is a css filter and stays outside the pixel work: the browser puts it over
the preview canvas, and the same filter is applied once more when the processed
copy is built, so what the eye saw is what leaves.

## Development

```bash
npm install
npm run dev     # the demo page
npm run build   # a build in public/magic-image/, for checking only
npm test        # the crop-mode table, in node, without a browser
```

`vite.config.js` is here for the local demo only — the component itself needs
no build. Every path in that file points into MagicPro, the project this
component grew in: the dependencies are taken from its `node_modules` and the
build lands in its `public` folder. Elsewhere those paths mean nothing, so set
them to your own folders.

Inside MagicPro the component is imported by a relative path
(`admin/js/magic-image/src/MagicImage.vue`), and the built demo is what the site
page embeds.

`npm publish` sends only `src/` and this file — see `files` in `package.json`.

`vue` and `vue-advanced-cropper` are taken from the node_modules of the MagicPro
package, so the build never ends up with two copies of Vue.
