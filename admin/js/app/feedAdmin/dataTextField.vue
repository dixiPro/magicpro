<script setup>
import { ref, watch, onMounted, onBeforeUnmount, useId } from 'vue';
import AceFileEditor from '../CommonCom/AceFileEditor.vue';
import { formatBlade } from '../ArtEditor/component/formatBlade.js';
import AceMdEditor from '../CommonCom/AceMdEditor.vue';
import htmlEditor from './htmlEditor.vue';
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

/**
 * Причесать разметку в Ace — тем же форматированием, что и в редакторе статей.
 *
 * Кривой html оно не портит: prettier не разобрал — возвращается исходный текст,
 * а сообщение показывает сам formatBlade.
 */
async function format() {
  value.value = await formatBlade(value.value, 2);
}
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
          :class="mode === 'wy' ? 'btn-primary' : 'btn-outline-primary'"
          @click="mode = 'wy'"
        >
          WYSIWYG
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'ace' ? 'btn-primary' : 'btn-outline-primary'"
          @click="mode = 'ace'"
        >
          ace
        </button>
      </div>

      <!-- форматирование только в Ace: в остальных вкладках правится не текст -->
      <button
        v-if="mode === 'ace'"
        type="button"
        class="btn btn-sm btn-outline-secondary ms-2"
        :title="t('format_code')"
        @click="format()"
      >
        <i class="fas fa-indent"></i>
      </button>

      <div v-if="mode === 'result'" class="border rounded p-2 preview" v-html="value"></div>
      <div v-else-if="mode === 'ace'" class="border rounded" style="height: 20rem">
        <AceFileEditor v-model="value" file-extention="html" theme="chrome" wrap />
      </div>
      <htmlEditor v-else v-model="value" />
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

      <div v-if="showPreview" class="border rounded p-2 mt-1 bg-white preview" v-html="preview"></div>
    </template>

    <!-- plain -->
    <textarea v-else v-model="value" rows="10" class="form-control form-control-sm font-monospace"></textarea>
  </div>
</template>

<style scoped>
/*
 * Текст оператора приходит с неразрывными пробелами: визуальный редактор ставит
 * их сам. Обычных пробелов в такой строке может не быть вовсе, переносить её
 * негде, и она уезжает за рамку блока. anywhere разрешает разрыв в любом месте —
 * но только когда иначе строка не помещается.
 *
 * :deep нужен потому, что содержимое приходит через v-html и области видимости
 * этого компонента не знает.
 */
.preview,
.preview :deep(*) {
  overflow-wrap: anywhere;
}

/* широкое остаётся широким: таблица или картинка получают свою прокрутку */
.preview {
  overflow-x: auto;
}
</style>
