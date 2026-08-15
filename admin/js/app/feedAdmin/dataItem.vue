<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { apiFeed } from './api.js';
import { formatDate } from '../CommonCom/formatDate.js';
import InputText from 'primevue/inputtext';
import dataTextField from './dataTextField.vue';
import dataImageField from './dataImageField.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  itemId: { type: [String, Number], required: true },
});

const itemId = computed(() => Number(props.itemId));

const feed = ref({ code: '', title: '', group_id: null, id: null });
const fields = ref([]); // поля схемы: код, подпись, тип, варианты для связи
const values = ref({}); // значения по логическим именам
const visible = ref(false);
const slug = ref(''); // адрес записи, системная колонка

// лента считает slug сама — править его руками нечем
const slugAuto = computed(() => (feed.value.schema?.slugFrom ?? '') !== '');

// тип вложенного поля __data превращаем в тот же набор, что у слотов
function dataTypeOf(type) {
  const map = {
    string: 'text',
    text: 'textarea',
    integer: 'number',
    decimal: 'number',
    boolean: 'checkbox',
    datetime: 'datetime-local',
    image: 'image',
  };

  // json и code пока правятся как текст
  return map[type] ?? 'textarea';
}

function typeOf(column) {
  if (column.startsWith('__string_')) return 'text';
  if (column.startsWith('__bigint_')) return 'number';
  if (column.startsWith('__date_')) return 'datetime-local';
  if (column.startsWith('__bool_')) return 'checkbox';
  if (column.startsWith('__link_')) return 'link';

  return null;
}

/**
 * Дата в инпут.
 *
 * В базе дата с пробелом, инпуту нужна буква T. Пустое поле заполняется текущей
 * датой: умолчания в схеме у дат нет, а новая запись создаётся пустой, и чаще
 * всего оператору нужно именно сегодня. Не нужно — правит руками.
 */
function toInput(value) {
  if (!value) return formatDate(new Date(), 'Y-m-dTH:i');

  return typeof value === 'string' ? value.replace(' ', 'T').slice(0, 16) : value;
}

function toStored(value) {
  return typeof value === 'string' && value !== '' ? value.replace('T', ' ') : value;
}

async function load() {
  try {
    const item = await apiFeed({ command: 'itemGet', id: itemId.value });

    visible.value = item.visible;
    slug.value = item.slug ?? '';

    feed.value = await apiFeed({ command: 'feedGet', id: item.feedId });

    const list = [];

    for (const field of feed.value.schema?.fields ?? []) {
      // контейнер __data: его вложенные поля тоже правятся здесь
      if (field.column === '__data') {
        for (const nested of field.data ?? []) {
          list.push({
            code: nested.code,
            label: nested.label || nested.code,
            column: '__data',
            type: dataTypeOf(nested.type),
            editor: nested.editor ?? 'plain',
            // ограничения картинки: ниже minWidth не сохранить, ratio держит рамку
            minWidth: Number(nested.minWidth ?? 0),
            ratio: nested.ratio ?? '',
            options: [],
          });

          values.value[nested.code] =
            nested.type === 'datetime' ? toInput(item.fields[nested.code]) : item.fields[nested.code];
        }

        continue;
      }

      const type = typeOf(field.column ?? '');

      if (!type) continue;

      const row = {
        code: field.code,
        label: field.label || field.code,
        column: field.column,
        type: type,
        options: [],
      };

      // для связи собираем список записей целевой ленты
      if (type === 'link' && field.relation) {
        try {
          row.options = await apiFeed({
            command: 'itemsLookup',
            feedId: field.relation.feed_id,
            column: field.relation.display_code,
          });
        } catch (error) {}
      }

      list.push(row);

      values.value[field.code] =
        type === 'datetime-local' ? toInput(item.fields[field.code]) : item.fields[field.code];
    }

    fields.value = inFormOrder(list);
  } catch (error) {}
}

/**
 * Поля в порядке, заданном на экране ленты (`orderForm` в схеме).
 *
 * Имя поля там же, что и в порядке колонок списка: у слота физическая колонка, у
 * поля `__data` его code. Чего в порядке нет — в конец, как пришло из схемы:
 * новое поле должно появиться в форме само, а не ждать, пока его перетащат.
 *
 * Порядок не задан — форма идёт как схема, то есть как было раньше.
 */
function inFormOrder(list) {
  const order = feed.value.schema?.orderForm ?? [];

  if (!Array.isArray(order) || order.length === 0) return list;

  const left = new Map(list.map((field) => [field.column === '__data' ? field.code : field.column, field]));

  const sorted = [];

  for (const name of order) {
    if (left.has(name)) {
      sorted.push(left.get(name));
      left.delete(name);
    }
  }

  return [...sorted, ...left.values()];
}

async function save() {
  const payload = {};

  for (const field of fields.value) {
    const value = values.value[field.code];

    payload[field.code] = field.type === 'datetime-local' ? toStored(value) : value;
  }

  try {
    await apiFeed({
      command: 'itemSave',
      id: itemId.value,
      fields: payload,
      visible: visible.value,
      slug: slug.value,
    });

    document.showToast(t('saved'));
    await load();
  } catch (error) {}
}

/**
 * Ctrl+S сохраняет запись.
 *
 * Ловим на окне и в фазе перехвата: фокус чаще всего стоит в редакторе текста, а
 * он гасит событие у себя. Своё сохранение страницы браузером при этом
 * перехватывается — в админке оно бессмысленно.
 *
 * Клавиша сравнивается по code, а не по key: при русской раскладке key приходит
 * буквой «ы».
 */
function onKeydown(event) {
  if (!(event.ctrlKey || event.metaKey) || event.code !== 'KeyS') return;

  event.preventDefault();

  save();
}

watch(itemId, () => load());

onMounted(() => {
  load();

  window.addEventListener('keydown', onKeydown, { capture: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown, { capture: true });
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink
        :to="feed.id ? { name: 'dataItems', params: { feedId: feed.id } } : { name: 'data' }"
        class="text-decoration-none me-2"
      >
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      {{ feed.title }}
      <span class="text-muted small">id {{ itemId }}</span>
    </h1>

    <div style="max-width: 100rem">
      <!-- адрес записи стоит первым: он про запись целиком, а не про её поля -->
      <div class="row mb-2">
        <label class="col-3 col-form-label col-form-label-sm">{{ t('feed_slug') }}</label>
        <div class="col-9">
          <InputText
            v-model="slug"
            :readonly="slugAuto"
            class="form-control form-control-sm"
            :class="{ 'bg-body-secondary': slugAuto }"
          />
        </div>
      </div>

      <div class="row mb-2">
        <label class="col-3 col-form-label col-form-label-sm">{{ t('feed_visible') }}</label>
        <div class="col-9">
          <div class="form-check pt-1">
            <input type="checkbox" v-model="visible" class="form-check-input" />
          </div>
        </div>
      </div>

      <div v-for="field in fields" :key="field.code" class="row mb-2">
        <label class="col-3 col-form-label col-form-label-sm">
          {{ field.label }}
          <span class="text-muted small d-block">{{ field.code }}</span>

          <!-- изображение показываем тут же, у подписи: справа только кнопки и alt -->
          <a
            v-if="field.type === 'image' && values[field.code]?.url"
            :href="values[field.code].url"
            target="_blank"
          >
            <img
              :src="values[field.code].url"
              :alt="values[field.code].alt ?? ''"
              class="border rounded d-block mt-1"
              style="max-height: 6rem; max-width: 100%"
            />
          </a>
        </label>
        <div class="col-9">
          <select v-if="field.type === 'link'" v-model="values[field.code]" class="form-select form-select-sm">
            <option :value="null">—</option>
            <option v-for="option in field.options" :key="option.id" :value="option.id">
              {{ option.value }}
            </option>
          </select>

          <div v-else-if="field.type === 'checkbox'" class="form-check pt-1">
            <input type="checkbox" v-model="values[field.code]" class="form-check-input" />
          </div>

          <dataImageField
            v-else-if="field.type === 'image'"
            v-model="values[field.code]"
            :item-id="itemId"
            :code="field.code"
            :min-width="field.minWidth"
            :ratio="field.ratio"
          />

          <dataTextField
            v-else-if="field.type === 'textarea'"
            v-model="values[field.code]"
            :editor="field.editor"
          />

          <InputText
            v-else-if="field.type === 'text'"
            v-model="values[field.code]"
            class="form-control form-control-sm"
          />

          <input
            v-else
            :type="field.type"
            v-model="values[field.code]"
            class="form-control form-control-sm"
          />
        </div>
      </div>

      <button class="btn btn-sm btn-primary" @click="save()">{{ t('save') }}</button>
    </div>
  </div>
</template>

<style scoped></style>
