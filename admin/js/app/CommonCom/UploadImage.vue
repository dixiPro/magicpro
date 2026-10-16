<script setup>
/**
 * A picture into the current folder: MagicImage crops and tunes it here, in
 * the browser, and only the crop goes to the server.
 *
 * The crop leaves MagicImage as png, without losses. What it becomes — webp,
 * jpg or avif, and with which quality — is decided on the next step, in
 * SaveImageFile, which also uploads it.
 */
import { ref } from 'vue';
import MagicImage from '../../magic-image/src/MagicImage.vue';
import SaveImageFile from './SaveImageFile.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n(); //

const emit = defineEmits(['uploaded']);

const props = defineProps({
  path: { type: String, required: true },
  directory: { type: Object, required: true }, // [{ name, type, mime, size, mtime }]
});

const show = ref(false);
const crop = ref(null); // { file, width, height, format }

function open() {
  crop.value = null;
  show.value = true;
}

function close() {
  show.value = false;
  crop.value = null;
}

// MagicImage gives the original and the crop; the folder needs the crop
function onSave(result) {
  crop.value = result.crop;
}

function onUploaded(fileInfo) {
  emit('uploaded', fileInfo);
  close();
}

defineExpose({ open, close });
</script>

<template>
  <Dialog v-model:visible="show" modal :header="t('upload_image')" style="width: 1000px; max-width: 96vw">
    <MagicImage v-if="!crop" output-format="png" :output-quality="100" @save="onSave" />

    <SaveImageFile v-else :crop="crop" :path="path" :directory="directory" @uploaded="onUploaded" @cancel="crop = null" />
  </Dialog>
</template>
