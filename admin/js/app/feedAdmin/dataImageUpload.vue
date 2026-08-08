<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { apiFeed, apiFeedFile } from './api.js';
import { translitString } from '../apiCall.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Загрузка изображения в поле записи: кнопка, окно, обрезка.
 *
 * До «Сохранить» на сервер не уходит ничего: файл читается в браузере, там же
 * режется, и уезжает уже то, что оператор выбрал. Иначе пришлось бы сперва
 * залить оригинал, а потом положить поверх обрезанный — и первый файл прожил бы
 * зря.
 *
 * Ограничения поля приходят из схемы: minWidth — ниже него сохранять нечего,
 * ratio — пропорции обрезки. Нет ratio — рамка свободная.
 *
 * Обрезка необязательна. Оригинал уходит как есть, со своим форматом; вырезанный
 * кусок берётся с canvas, поэтому уходит png или jpeg — как задано в настройках, —
 * а в рабочий формат его кодирует сервер.
 */
const props = defineProps({
  itemId: { type: [String, Number], required: true },
  code: { type: String, required: true },
  minWidth: { type: Number, default: 0 },
  ratio: { type: String, default: '' },
});

const emit = defineEmits(['uploaded']);

const open = ref(false);
const input = ref(null);

const file = ref(null); // выбранный файл, как есть
const imgSrc = ref(null); // он же для показа
const fileName = ref(''); // имя без расширения, его правит оператор
const extension = ref('');

const imageWidth = ref(0);
const imageHeight = ref(0);

// чем отправлять обрезанный кусок, приходит из настроек: png или jpeg
const uploadFormat = ref('png');

const cropperRef = ref(null);
const cropperActive = ref(false);
const cropWidth = ref(0);
const cropHeight = ref(0);

// «16/9» -> 1.777…, пустое -> свободная рамка
const aspectRatio = computed(() => {
  const [w, h] = (props.ratio || '').split('/').map(Number);

  return w && h ? w / h : undefined;
});

const canSaveImage = computed(() => imageWidth.value >= props.minWidth);
const canSaveCrop = computed(() => cropWidth.value >= props.minWidth);

// имя файла уедет в url, поэтому только латиница
watch(fileName, (name) => {
  const clean = translitString(name);

  if (clean !== name) fileName.value = clean;
});

// размеры выбранной картинки: по ним решается, можно ли её вообще брать
watch(imgSrc, (src) => {
  if (!src) return;

  const image = new Image();

  image.onload = () => {
    imageWidth.value = image.naturalWidth;
    imageHeight.value = image.naturalHeight;
  };

  image.src = src;
});

function take(picked) {
  file.value = picked;

  const parts = picked.name.split('.');

  extension.value = parts.length > 1 ? parts.pop() : '';
  fileName.value = translitString(parts.join('.'));

  const reader = new FileReader();

  reader.onload = (event) => {
    imgSrc.value = event.target.result;
  };

  reader.readAsDataURL(picked);
}

function onFileSelect(event) {
  const picked = event.target.files?.[0];

  if (picked) take(picked);

  // сбрасываем, иначе повторный выбор того же файла не даст события
  event.target.value = '';
}

// картинка из буфера приходит файлом без имени — своё оператор напишет сам
function onPaste(event) {
  if (!open.value) return;

  for (const item of event.clipboardData?.items ?? []) {
    if (item.type.startsWith('image/')) {
      take(item.getAsFile());

      cropperActive.value = aspectRatio.value !== undefined;

      break;
    }
  }
}

function onCropChange({ canvas }) {
  if (!canvas) return;

  cropWidth.value = canvas.width;
  cropHeight.value = canvas.height;
}

// вырезанный кусок берётся с canvas, а это всегда картинка заново. Браузер шлёт
// голые пиксели — png или jpeg, как сказал сервер, — а в рабочий формат кодирует
// сервер сам, командой imageCropUpload
function croppedFile() {
  const { canvas } = cropperRef.value.getResult();

  if (!canvas) return null;

  const type = 'image/' + uploadFormat.value;
  const name = fileName.value + '.' + (uploadFormat.value === 'jpeg' ? 'jpg' : uploadFormat.value);

  return new Promise((resolve) => {
    canvas.toBlob((blob) => resolve(new File([blob], name, { type })), type, 0.95);
  });
}

async function save() {
  const cropped = cropperActive.value;

  const ready = cropped
    ? await croppedFile()
    : new File([file.value], fileName.value + '.' + extension.value, { type: file.value.type });

  if (!ready) return;

  try {
    const command = cropped ? 'imageCropUpload' : 'imageUpload';

    const data = await apiFeedFile({ command, id: props.itemId, code: props.code }, ready);

    emit('uploaded', data);

    document.showToast(t('saved'));

    close();
  } catch (error) {}
}

function close() {
  open.value = false;
  file.value = null;
  imgSrc.value = null;
  fileName.value = '';
  extension.value = '';
  imageWidth.value = 0;
  imageHeight.value = 0;
  cropperActive.value = false;
  cropWidth.value = 0;
  cropHeight.value = 0;
}

onMounted(async () => {
  window.addEventListener('paste', onPaste);

  // не ответил — останется png: без формата отправлять всё равно надо чем-то
  try {
    uploadFormat.value = await apiFeed({ command: 'uploadFormat' });
  } catch (error) {}
});

onUnmounted(() => {
  window.removeEventListener('paste', onPaste);
});
</script>

<template>
  <button class="btn btn-sm btn-outline-primary me-1" @click="open = true">
    {{ t('feed_image_upload') }}
  </button>

  <Dialog
    :visible="open"
    modal
    :draggable="false"
    :header="t('feed_image_upload')"
    :style="{ width: '52rem' }"
    @update:visible="close()"
  >
    <div class="row mb-3">
      <!--
        Свой выбор файла вместо родного контрола: тот подписывает себя сам,
        языком браузера, и в английской админке остаётся русским.
      -->
      <div class="col-8 d-flex align-items-center gap-2">
        <input ref="input" type="file" accept="image/*" class="d-none" @change="onFileSelect" />

        <button class="btn btn-sm btn-outline-secondary" @click="input.click()">
          {{ t('feed_image_choose') }}
        </button>

        <span class="text-muted small text-truncate">
          {{ file ? file.name : t('feed_image_not_chosen') }}
        </span>
      </div>
      <div class="col-4 text-muted small pt-1">{{ t('feed_image_paste') }}</div>
    </div>

    <div v-if="imgSrc" class="row">
      <div class="col-7">
        <Cropper
          v-if="cropperActive"
          ref="cropperRef"
          :src="imgSrc"
          :stencil-props="{ aspectRatio: aspectRatio }"
          style="height: 24rem"
          @change="onCropChange"
        />

        <img v-else :src="imgSrc" class="border rounded" style="max-width: 100%; max-height: 24rem" />
      </div>

      <div class="col-5">
        <label class="form-label mb-0 small">{{ t('feed_image_name') }}</label>
        <div class="input-group input-group-sm mb-2">
          <input v-model="fileName" class="form-control form-control-sm" />
          <span class="input-group-text">.{{ cropperActive ? uploadFormat : extension }}</span>
        </div>

        <div v-if="minWidth" class="small text-muted">
          {{ t('feed_image_min') }} {{ minWidth }}px<span v-if="ratio"> · {{ ratio }}</span>
        </div>

        <div class="small text-muted mb-3">
          {{ t('feed_image_current') }}
          <template v-if="cropperActive">{{ cropWidth }} × {{ cropHeight }}</template>
          <template v-else>{{ imageWidth }} × {{ imageHeight }}</template>
        </div>

        <template v-if="cropperActive">
          <button v-if="canSaveCrop" class="btn btn-sm btn-primary me-1" @click="save()">
            {{ t('save') }}
          </button>
          <button class="btn btn-sm btn-outline-secondary me-1" @click="cropperActive = false">
            {{ t('feed_image_back') }}
          </button>
        </template>

        <template v-else>
          <button v-if="canSaveImage" class="btn btn-sm btn-primary me-1" @click="save()">
            {{ t('save') }}
          </button>
          <button v-if="canSaveImage" class="btn btn-sm btn-outline-primary me-1" @click="cropperActive = true">
            {{ t('feed_image_crop') }}
          </button>
        </template>

        <button class="btn btn-sm btn-outline-secondary" @click="close()">{{ t('cancel') }}</button>
      </div>
    </div>
  </Dialog>
</template>

<style scoped></style>
