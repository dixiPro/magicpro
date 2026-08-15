<script setup>
import { ref, watch, computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Визуальный редактор html. Внутри TipTap, снаружи — только `v-model` со
 * строкой html.
 *
 * Панель своя целиком: движок даёт команды, кнопки рисуем сами. Поэтому в ней
 * ровно то, что нужно тексту записи, а не полсотни чужих кнопок.
 *
 * Разрешённая разметка описана схемой редактора, и это же список того, что
 * переживёт вставку из Word: чего в схеме нет, то при разборе отваливается само.
 * Ни цветов, ни шрифтов, ни выравнивания, ни служебных классов — оформление
 * задаёт тема сайта.
 */
const value = defineModel({ type: String, default: '' });

// Пустой документ TipTap отдаёт как <p></p>. Наружу это должно быть пусто:
// иначе «поле не заполнено» и «поле с пустым абзацем» — разные вещи для всех,
// кто читает запись.
const EMPTY = '<p></p>';

function outgoing(html) {
  return html === EMPTY ? '' : html;
}

/**
 * Вставка.
 *
 * Word и Google Docs ставят неразрывный пробел вместо каждого обычного. Строка
 * из таких пробелов не переносится и вылезает за край колонки, поэтому меняем их
 * на обычные ещё до разбора. Всё остальное — шрифты, цвета, классы — отсечёт
 * схема.
 *
 * Ctrl+Shift+V работает сам по себе: там в буфере только текст.
 */
function transformPastedHTML(html) {
  return html.replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
}

const editor = useEditor({
  content: value.value,
  extensions: [
    StarterKit.configure({
      // заголовка первого уровня нет намеренно: на странице он один, и он выше
      // записи. Вставленный h1 схема превратит в обычный абзац
      heading: { levels: [2, 3] },

      link: { openOnClick: false },

      // тексту записи не нужны: код и линейка — это про документацию, а
      // подчёркивание в вебе читается как ссылка
      code: false,
      codeBlock: false,
      horizontalRule: false,
      underline: false,
    }),
  ],
  editorProps: {
    transformPastedHTML,
    attributes: {
      class: 'form-control h-100 overflow-auto',
    },
  },
  onUpdate: ({ editor }) => {
    value.value = outgoing(editor.getHTML());
  },
});

// Правка снаружи — например, руками в Ace, а потом обратно на вкладку Wy.
// Сравниваем с тем, что уже в редакторе: иначе своя же правка вернулась бы
// обратно и сбивала курсор на каждую букву.
watch(value, (html) => {
  if (!editor.value || outgoing(editor.value.getHTML()) === (html ?? '')) return;

  editor.value.commands.setContent(html ?? '', { emitUpdate: false });
});

// Уровень заголовка для выпадающего списка: 0 — обычный абзац
const headingLevel = computed({
  get() {
    if (!editor.value) return '0';

    if (editor.value.isActive('heading', { level: 2 })) return '2';
    if (editor.value.isActive('heading', { level: 3 })) return '3';

    return '0';
  },
  set(level) {
    const chain = editor.value.chain().focus();

    level === '0' ? chain.setParagraph().run() : chain.setHeading({ level: Number(level) }).run();
  },
});

// === ссылка ===

// адрес правится своей строкой, а не окном браузера: alert и prompt вешают
// страницу и выглядят чужеродно
const linkOpen = ref(false);
const linkHref = ref('');

function openLink() {
  if (!editor.value) return;

  linkHref.value = editor.value.getAttributes('link').href ?? '';
  linkOpen.value = true;
}

function applyLink() {
  const href = linkHref.value.trim();
  const chain = editor.value.chain().focus().extendMarkRange('link');

  href === '' ? chain.unsetLink().run() : chain.setLink({ href }).run();

  linkOpen.value = false;
}

function removeLink() {
  editor.value.chain().focus().extendMarkRange('link').unsetLink().run();

  linkOpen.value = false;
}

/** Снять всё оформление с выделенного и развалить заголовки в абзацы. */
function clearFormat() {
  editor.value.chain().focus().unsetAllMarks().clearNodes().run();
}
</script>

<template>
  <div v-if="editor">
    <div class="btn-toolbar gap-2 mb-1">
      <select v-model="headingLevel" class="form-select form-select-sm" style="width: 10rem">
        <option value="2">{{ t('feed_text_heading') }}</option>
        <option value="3">{{ t('feed_text_subheading') }}</option>
        <option value="0">{{ t('feed_text_normal') }}</option>
      </select>

      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn"
          :class="editor.isActive('bold') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleBold().run()"
        >
          <i class="fas fa-bold"></i>
        </button>
        <button
          type="button"
          class="btn"
          :class="editor.isActive('italic') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleItalic().run()"
        >
          <i class="fas fa-italic"></i>
        </button>
        <button
          type="button"
          class="btn"
          :class="editor.isActive('strike') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleStrike().run()"
        >
          <i class="fas fa-strikethrough"></i>
        </button>
      </div>

      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn"
          :class="editor.isActive('bulletList') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleBulletList().run()"
        >
          <i class="fas fa-list-ul"></i>
        </button>
        <button
          type="button"
          class="btn"
          :class="editor.isActive('orderedList') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleOrderedList().run()"
        >
          <i class="fas fa-list-ol"></i>
        </button>
        <button
          type="button"
          class="btn"
          :class="editor.isActive('blockquote') ? 'btn-primary' : 'btn-outline-secondary'"
          @click="editor.chain().focus().toggleBlockquote().run()"
        >
          <i class="fas fa-quote-right"></i>
        </button>
      </div>

      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn"
          :class="editor.isActive('link') ? 'btn-primary' : 'btn-outline-secondary'"
          :title="t('feed_text_link')"
          @click="openLink()"
        >
          <i class="fas fa-link"></i>
        </button>
        <button
          type="button"
          class="btn btn-outline-secondary"
          :title="t('feed_text_clear')"
          @click="clearFormat()"
        >
          <i class="fas fa-eraser"></i>
        </button>
      </div>

      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="!editor.can().undo()"
          @click="editor.chain().focus().undo().run()"
        >
          <i class="fas fa-undo"></i>
        </button>
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="!editor.can().redo()"
          @click="editor.chain().focus().redo().run()"
        >
          <i class="fas fa-redo"></i>
        </button>
      </div>
    </div>

    <!-- адрес ссылки: пустая строка снимает ссылку с выделенного -->
    <div v-if="linkOpen" class="input-group input-group-sm mb-1">
      <input
        v-model="linkHref"
        class="form-control"
        :placeholder="t('feed_text_link_href')"
        @keydown.enter.prevent="applyLink()"
        @keydown.esc.prevent="linkOpen = false"
      />
      <button type="button" class="btn btn-primary" @click="applyLink()">{{ t('ok') }}</button>
      <button type="button" class="btn btn-outline-secondary" @click="removeLink()">
        <i class="fas fa-unlink"></i>
      </button>
    </div>

    <EditorContent :editor="editor" class="editor" />
  </div>
</template>

<style scoped>
/* Высота как у соседних редакторов, Ace и textarea: поле не должно прыгать при
   переключении вкладок */
.editor :deep(.ProseMirror) {
  height: 20rem;
}

.editor :deep(.ProseMirror:focus) {
  outline: none;
}

/* Текст из Word приходит без единого обычного пробела, переносить его негде.
   anywhere разрешает разрыв в любом месте — но только когда иначе не влезает */
.editor :deep(.ProseMirror) * {
  overflow-wrap: anywhere;
}
</style>
