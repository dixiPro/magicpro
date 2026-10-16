<script setup>
/**
 * The last step of a picture: its name, the format, the quality and the upload.
 *
 * The browser only delivers pixels. MagicImage hands the crop over as png,
 * lossless, and the working format is made by the server — the same encoders
 * the resizer uses (`uploadImage` of the file manager api). That is why avif is
 * in the list at all: no browser can encode it.
 */
import { ref, computed, onUnmounted } from 'vue';
import { csrfToken } from '../apiCall.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n(); //

const emit = defineEmits(['uploaded', 'cancel']);

const props = defineProps({
  // the crop of MagicImage: { file, width, height, format }
  crop: { type: Object, required: true },
  path: { type: String, required: true },
  directory: { type: Object, required: true }, // [{ name, type, mime, size, mtime }]
});

// the defaults of the resize settings, so the field starts where the site
// itself would have started
const FORMATS = [
  { value: 'webp', quality: 82 },
  { value: 'jpg', quality: 70 },
  { value: 'avif', quality: 50 },
];

const format = ref('webp');
const quality = ref(82);
const baseName = ref(stripExtension(props.crop.file.name));

const uploading = ref(false);

const previewUrl = ref(URL.createObjectURL(props.crop.file));

const fileName = computed(() => baseName.value.trim() + '.' + format.value);

const existingNames = computed(() => {
  return new Set((props.directory ?? []).map((it) => (it.name ?? '').toLowerCase()));
});

const isDuplicate = computed(() => existingNames.value.has(fileName.value.toLowerCase()));

function stripExtension(name) {
  return name.includes('.') ? name.slice(0, name.lastIndexOf('.')) : name;
}

// each format has its own scale of quality: 82 for webp and 82 for avif are
// different pictures and different weights
function onFormatChange() {
  quality.value = FORMATS.find((f) => f.value === format.value)?.quality ?? 82;
}

async function upload() {
  if (uploading.value || baseName.value.trim() === '') return;

  uploading.value = true;

  try {
    const params = new URLSearchParams({
      command: 'uploadImage',
      path: props.path,
      filename: fileName.value,
      format: format.value,
      quality: String(Math.min(Math.max(quality.value, 1), 100)),
    });

    const res = await fetch(`/a_dmin/api/fileManager?${params.toString()}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Content-Type': 'application/octet-stream',
      },
      body: props.crop.file,
    }).then((r) => r.json());

    if (!res?.status) throw new Error(res?.errorMsg || 'Upload error');

    emit('uploaded', res.data);
  } catch (error) {
    document.showToast(error.message || 'File upload error', 'error');
  } finally {
    uploading.value = false;
  }
}

onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});
</script>

<template>
  <div class="d-flex">
    <div class="me-3" style="flex: 0 0 260px; max-width: 45%">
      <img :src="previewUrl" alt="" style="width: 100%; height: auto; display: block" class="rounded border" />
    </div>

    <div class="flex-grow-1">
      <div class="mb-2">
        <small>{{ crop.width }} × {{ crop.height }}</small>
      </div>

      <div class="mb-2">
        <input v-model="baseName" type="text" class="form-control" :placeholder="t('name_file')" @keyup.enter="upload" />
      </div>

      <div class="d-flex align-items-center mb-2">
        <select v-model="format" class="form-select me-2" style="width: 120px" @change="onFormatChange">
          <option v-for="f in FORMATS" :key="f.value" :value="f.value">{{ f.value }}</option>
        </select>

        <div class="me-2">{{ t('image_quality') }}</div>
        <input v-model.number="quality" type="number" min="1" max="100" step="1" class="form-control" style="width: 90px" />
      </div>

      <div class="mb-2">
        <small>{{ fileName }} — {{ t('image_encoded_on_server') }}</small>
      </div>

      <div v-if="isDuplicate" class="text-danger mb-2">{{ t('duplicate_overwrite') }}</div>

      <div>
        <button class="btn btn-success btn-sm me-2" :disabled="uploading" @click="upload">{{ t('save') }}</button>
        <button class="btn btn-secondary btn-sm" :disabled="uploading" @click="emit('cancel')">{{ t('cancel') }}</button>
      </div>
    </div>
  </div>
</template>
