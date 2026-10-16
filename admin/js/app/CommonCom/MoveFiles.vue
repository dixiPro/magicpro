<script setup>
/**
 * Where to move: a folder is chosen by walking, not by typing a path.
 *
 * The window shows one folder at a time — the subfolders of the current one and
 * the way up, not deeper than the root of the manager. The move itself is the
 * `rename` command: on one disk it moves a file or a whole folder, and it
 * refuses a name that is taken, so nothing is overwritten silently.
 */
import { ref, computed, watch } from 'vue';
import { apiFile } from '../apiCall';

import { useI18n } from 'vue-i18n';
const { t } = useI18n(); //

import ModalWindow from './ModalWindow.vue';

const visible = defineModel('visible', { type: Boolean, default: false });

const emit = defineEmits(['moved']);

const props = defineProps({
  // names of the items being moved, all of them from `fromPath`
  names: { type: Array, default: () => [] },
  fromPath: { type: String, default: '' },
  startDirectory: { type: String, default: '/design/' },

  // items of the current folder, to tell a folder from a file by its name
  directory: { type: Array, default: () => [] },
});

const current = ref('');
const dirs = ref([]);
const busy = ref(false);

const canUp = computed(() => current.value.replace(/\/+$/, '') !== props.startDirectory.replace(/\/+$/, ''));

// nothing to do when the target is the folder the items already lie in
const same = computed(() => current.value === props.fromPath);

// a folder cannot be moved into itself or into anything inside it
const intoItself = computed(() => {
  return props.names.some((name) => {
    const item = props.directory.find((el) => el.name === name);

    if (item?.type !== 'dir') return false;

    const inside = props.fromPath + name + '/';

    return current.value === inside || current.value.startsWith(inside);
  });
});

async function load(pathVal) {
  busy.value = true;

  try {
    const list = await apiFile({ command: 'dirList', path: pathVal });

    dirs.value = (list ?? []).filter((el) => el.type === 'dir');
    current.value = pathVal;
  } catch (e) {
    console.error(e);
  } finally {
    busy.value = false;
  }
}

function enter(name) {
  load(current.value + name + '/');
}

function up() {
  const parts = current.value.split('/').filter((el) => el !== '');

  parts.pop();

  load('/' + parts.join('/') + '/');
}

// the window opens on the folder the items come from: usually the neighbour is
// one step away
watch(visible, (on) => {
  if (on) load(props.fromPath || props.startDirectory);
});

/**
 * Every name is moved on its own: a name that is taken on the other side is
 * refused by the server, and that must not cancel the rest.
 */
async function move() {
  if (busy.value || same.value || intoItself.value || props.names.length === 0) return;

  busy.value = true;

  const gone = [];

  for (const name of props.names) {
    try {
      await apiFile({
        command: 'rename',
        oldName: props.fromPath + name,
        newName: current.value + name,
      });

      gone.push(name);
    } catch (e) {
      console.error(e);
      document.showToast(t('move') + ' ' + name + ': ' + (e?.message ?? ''), 'error');
    }
  }

  busy.value = false;

  if (gone.length) {
    document.showToast(t('moved') + ' ' + gone.length);
    emit('moved', gone);
  }

  visible.value = false;
}
</script>

<template>
  <ModalWindow v-model:visible="visible" :zIndex="1100" :initialWidth="620" :initialHeight="460" @close="visible = false">
    <template #header>
      <div class="d-flex align-items-center gap-2 p-1 w-100">
        <div class="flex-grow-1 text-truncate">{{ t('move') }}: {{ names.length }}</div>
      </div>
    </template>

    <div class="p-2">
      <div class="mb-2 text-truncate"><code>{{ current }}</code></div>

      <div class="border rounded p-2 mb-2" style="height: 240px; overflow: auto">
        <div v-if="canUp" class="d-flex my-1 pointer" @click="up">
          <div class="me-2"><i class="far fa-folder-open"></i></div>
          <div>..</div>
        </div>

        <div v-for="el in dirs" :key="el.name" class="d-flex my-1 pointer" @click="enter(el.name)">
          <div class="me-2"><i class="far fa-folder"></i></div>
          <div>{{ el.name }}</div>
        </div>

        <div v-if="!canUp && dirs.length === 0" class="text-muted">...</div>
      </div>

      <div v-if="same" class="text-muted mb-2"><small>{{ t('move_same_folder') }}</small></div>
      <div v-if="intoItself" class="text-danger mb-2"><small>{{ t('move_into_itself') }}</small></div>

      <button class="btn btn-success btn-sm me-2" :disabled="busy || same || intoItself" @click="move">{{ t('move') }}</button>
      <button class="btn btn-secondary btn-sm" :disabled="busy" @click="visible = false">{{ t('cancel') }}</button>
    </div>
  </ModalWindow>
</template>
