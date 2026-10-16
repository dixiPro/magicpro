<script setup>
/**
 * A look at one file of the folder: a picture, a video, a sound or a pdf.
 *
 * Nothing is loaded through the api — the folder of the manager lies under
 * `public`, so the browser takes the file by its own address. The kind is
 * decided by the extension, and the list of kinds is the one the manager
 * opens by a click, see `mediaKind`.
 */
import { ref, computed } from 'vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n(); //

import ModalWindow from './ModalWindow.vue';

const visible = defineModel('visible', { type: Boolean, default: false });

const props = defineProps({
  // one item of the directory: { name, size, width, height, date, ... }
  el: { type: Object, default: null },
  path: { type: String, default: '' },
  kind: { type: String, default: '' }, // image | video | audio | pdf
});

// the date is a cache buster: a file replaced under the same name has to be
// shown as it is now, not as the browser remembers it
const src = computed(() => encodeURI(props.path + (props.el?.name ?? '')) + '?' + (props.el?.date ?? ''));

const size = computed(() => {
  const bytes = Number(props.el?.size ?? 0);

  if (bytes > 1000000) return (bytes / 1000000).toFixed(1) + ' m';
  if (bytes > 1000) return (bytes / 1000).toFixed(1) + ' k';

  return bytes + ' b';
});
</script>

<template>
  <ModalWindow v-if="el" v-model:visible="visible" :zIndex="1100" :initialWidth="900" :initialHeight="650" @close="visible = false">
    <template #header>
      <div class="d-flex align-items-center gap-2 p-1 w-100">
        <div class="flex-grow-1 text-truncate">{{ el.name }}</div>

        <small class="text-muted">
          <span v-if="el.width">{{ el.width }} × {{ el.height }}, </span>{{ size }}
        </small>
      </div>
    </template>

    <div class="media-view">
      <img v-if="kind === 'image'" :src="src" :alt="el.name" class="media-view__pic" />

      <video v-else-if="kind === 'video'" :src="src" controls class="media-view__pic"></video>

      <audio v-else-if="kind === 'audio'" :src="src" controls class="w-100"></audio>

      <iframe v-else-if="kind === 'pdf'" :src="src" class="media-view__frame" :title="el.name"></iframe>

      <div v-else class="text-muted">{{ t('nothing_to_open') }}</div>
    </div>
  </ModalWindow>
</template>

<style scoped>
.media-view {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 0.5rem;
  overflow: auto;
}

/* the picture fits the window whatever its proportion is */
.media-view__pic {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.media-view__frame {
  width: 100%;
  height: 100%;
  border: 0;
}
</style>
