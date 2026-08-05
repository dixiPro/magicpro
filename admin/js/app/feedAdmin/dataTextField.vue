<script setup>
import { ref, watch, onMounted, onBeforeUnmount, useId } from 'vue';
import AceFileEditor from '../CommonCom/AceFileEditor.vue';
import AceMdEditor from '../CommonCom/AceMdEditor.vue';
import Editor from 'primevue/editor';
import { apiFeed } from './api.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Текстовое поле записи. Как его править, решает editor из схемы:
 *
 *   plain    — просто textarea;
 *   html     — «результат», Ace с подсветкой или визуальный редактор, по кнопкам;
 *   markdown — Ace в режиме markdown, под ним предпросмотр.
 *
 * Markdown в html превращает сервер, командой mdToHtml: тем же вызовом, каким
 * запись рендерится на сайте. Предпросмотр, считающий текст сам, рано или
 * поздно разошёлся бы со страницей.
 */
const props = defineProps({
  editor: { type: String, default: 'plain' },
});

const value = defineModel({ type: String, default: '' });

// 'result' | 'ace' | 'wy', только для html
const mode = ref('result');

const previewId = useId();
const showPreview = ref(true);
const preview = ref('');

// текст уезжает на сервер не на каждую букву, а когда человек остановился
const DELAY = 500;

let timer = null;
let seq = 0;

function schedule() {
  if (props.editor !== 'markdown' || !showPreview.value) return;

  clearTimeout(timer);

  timer = setTimeout(render, DELAY);
}

async function render() {
  const my = ++seq;

  try {
    const res = await apiFeed({ command: 'mdToHtml', md: value.value });

    // пока ждали, текст успели поменять — этот ответ уже не про него
    if (my === seq) preview.value = res.html;
  } catch (error) {}
}

watch(value, schedule);

watch(showPreview, (on) => {
  if (on) render();
});

onMounted(() => {
  if (props.editor === 'markdown' && showPreview.value) render();
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
  <div>
    <!-- html: правка или результат, по кнопкам -->
    <template v-if="editor === 'html'">
      <div class="btn-group btn-group-sm mb-1" role="group">
        <button
          type="button"
          class="btn"
          :class="mode === 'result' ? 'btn-primary' : 'btn-outline-primary'"
          @click="mode = 'result'"
        >
          {{ t('feed_text_result') }}
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'ace' ? 'btn-primary' : 'btn-outline-primary'"
          @click="mode = 'ace'"
        >
          ace
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'wy' ? 'btn-primary' : 'btn-outline-primary'"
          @click="mode = 'wy'"
        >
          Wy
        </button>
      </div>

      <div v-if="mode === 'result'" class="border rounded p-2" v-html="value"></div>
      <div v-else-if="mode === 'ace'" class="border rounded" style="height: 20rem">
        <AceFileEditor v-model="value" file-extention="html" theme="chrome" wrap />
      </div>
      <Editor v-else v-model="value" editor-style="height: 20rem" />
    </template>

    <!-- markdown: редактор, под ним результат -->
    <template v-else-if="editor === 'markdown'">
      <div class="form-check form-switch small mb-1">
        <input :id="previewId" v-model="showPreview" class="form-check-input" type="checkbox" />
        <label :for="previewId" class="form-check-label">{{ t('feed_text_preview') }}</label>
      </div>

      <div class="border rounded" style="height: 20rem">
        <AceMdEditor v-model="value" theme="chrome" />
      </div>

      <div v-if="showPreview" class="border rounded p-2 mt-1 bg-white" v-html="preview"></div>
    </template>

    <!-- plain -->
    <textarea v-else v-model="value" rows="10" class="form-control form-control-sm font-monospace"></textarea>
  </div>
</template>

<style scoped></style>
