<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
import { formatDate } from '../CommonCom/formatDate.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  feedId: { type: [String, Number], required: true },
});

const PER_PAGE = 20;

const router = useRouter();

const feedId = computed(() => Number(props.feedId));

const feed = ref({ code: '', title: '', group_id: null });
const columns = ref([]); // логические имена полей ленты, как заголовки таблицы
const items = ref([]);
const markdownHtml = ref({}); // готовый html ячеек с markdown, ключ «id:code»
const linkText = ref({}); // заголовки связанных записей, ключ «feedId:id»
const displayCodes = new Map(); // лента справочника → code поля, которым её показываем
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

/**
 * Порядок записей.
 *
 * По умолчанию — свой порядок ленты, большие номера сверху.
 *
 * Сортировать сервер умеет по колонкам, поэтому стрелки стоят у слотов;
 * значения json-контейнера, а с ними и картинки, отбирать нечем.
 *
 * Порядок вручную живёт в __position, и тащить строку можно только когда список
 * стоит на нём: в любом другом порядке место строки ничего не значит.
 */
const orderBy = ref('__position');
const direction = ref('desc');

const byPosition = computed(() => orderBy.value === '__position');

function sortable(column) {
  return typeof column.name === 'string' && column.name.startsWith('__');
}

function sorted(code, dir) {
  return orderBy.value === code && direction.value === dir;
}

async function sortBy(code, dir) {
  orderBy.value = code;
  direction.value = dir;
  page.value = 1;

  try {
    await loadItems();
  } catch (error) {}
}

/**
 * Колонки списка.
 *
 * Состав задаёт схема: showOnList. Ключа нет — поле показывается, так что
 * лишнее видно сразу и убирается, а не пропадает молча.
 *
 * Поля __data идут наравне со слотами: картинку в списке видеть надо, а лежит
 * она именно там. Тип нужен, чтобы отличить её от текста, editor — чтобы
 * показать текст так же, как его правят.
 */
async function loadFeed() {
  const data = await apiFeed({ command: 'feedGet', id: feedId.value });

  feed.value = data;

  const list = [];

  for (const field of data.schema?.fields ?? []) {
    if (field.column === '__data') {
      for (const nested of field.data ?? []) {
        if (nested.showOnList === false) continue;

        list.push({
          name: nested.code,
          code: nested.code,
          label: nested.label,
          type: nested.type,
          editor: nested.editor ?? 'plain',
          listFormat: nested.listFormat ?? '',
        });
      }

      continue;
    }

    if (!field.column || field.showOnList === false) continue;

    list.push({
      name: field.column,
      code: field.code,
      label: field.label,
      // тип слота нужен, чтобы узнать дату (её показываем по маске) и связь
      // (в колонке лежит id, а показать надо запись)
      type: field.column.startsWith('__date_')
        ? 'datetime'
        : field.column.startsWith('__link_')
          ? 'link'
          : '',
      relation: field.relation ?? null,
      editor: field.editor ?? 'plain',
      listFormat: field.listFormat ?? '',
    });
  }

  // порядок колонок задаёт order; чего в нём нет — после, как лежит в схеме
  const order = Array.isArray(data.schema?.order) ? data.schema.order : [];

  const place = (column) => {
    const index = order.indexOf(column.name);

    return index === -1 ? order.length : index;
  };

  columns.value = list.sort((first, second) => place(first) - place(second));
}

async function loadItems() {
  const data = await apiFeed({
    command: 'itemsList',
    feedId: feedId.value,
    page: page.value,
    perPage: PER_PAGE,
    filter: activeFilters.value,
    orderBy: orderBy.value,
    direction: direction.value,
  });

  items.value = data.items;
  lastPage.value = data.lastPage;
  total.value = data.total;

  await renderMarkdown();
  await loadLinks();
}

/**
 * Записи, на которые ссылаются поля связи.
 *
 * В колонке связи лежит id, поэтому без этого в списке стояло бы число. Сайт
 * разворачивает связь так же: берёт ленту-справочник и достаёт из неё название.
 *
 * Читаем только те id, что есть на странице, и только новые: id повторяется из
 * строки в строку, а справочник от страницы к странице тот же.
 */
async function loadLinks() {
  for (const column of columns.value) {
    if (column.type !== 'link' || !column.relation) continue;

    const code = await displayCode(column.relation);

    if (!code) continue;

    const ids = [...new Set(items.value.map((item) => item.fields[column.code]).filter(Boolean))];

    await Promise.all(
      ids.map(async (id) => {
        const key = column.relation.feed_id + ':' + id;

        if (linkText.value[key] !== undefined) return;

        try {
          const target = await apiFeed({ command: 'itemGet', id: id });

          linkText.value[key] = target.fields[code] ?? '';
        } catch (error) {
          linkText.value[key] = '';
        }
      }),
    );
  }
}

/**
 * Каким полем показываем запись справочника. В схеме связи записана его
 * колонка, а запись отдаётся под логическими именами, поэтому нужен code.
 */
async function displayCode(relation) {
  if (!displayCodes.has(relation.feed_id)) {
    try {
      const target = await apiFeed({ command: 'feedGet', id: relation.feed_id });

      displayCodes.set(
        relation.feed_id,
        (target.schema?.fields ?? []).find((field) => field.column === relation.display_code)?.code ?? '',
      );
    } catch (error) {
      displayCodes.set(relation.feed_id, '');
    }
  }

  return displayCodes.get(relation.feed_id);
}

/**
 * markdown-ячейки страницы.
 *
 * Переводит в html сервер, тем же методом, что и сайт: свой преобразователь в
 * браузере рано или поздно разошёлся бы с ним, и список показывал бы не то, что
 * увидит посетитель.
 *
 * Запрос на ячейку, зато без новых команд API. Страница — двадцать записей,
 * ошибку одной ячейки молча пропускаем: список из-за неё падать не должен.
 */
async function renderMarkdown() {
  markdownHtml.value = {};

  const marked = columns.value.filter((column) => column.editor === 'markdown');

  if (marked.length === 0) return;

  await Promise.all(
    items.value.flatMap((item) =>
      marked.map(async (column) => {
        const md = item.fields[column.code];

        if (!md) return;

        try {
          const data = await apiFeed({ command: 'mdToHtml', md: md });

          markdownHtml.value[item.id + ':' + column.code] = data.html;
        } catch (error) {}
      }),
    ),
  );
}

async function load() {
  try {
    await loadFeed();
    await loadItems();
  } catch (error) {}
}

// новая запись создаётся пустой и скрытой, поэтому сразу открываем её форму
async function createItem() {
  try {
    const item = await apiFeed({ command: 'itemCreate', feedId: feedId.value });

    router.push({ name: 'dataItem', params: { itemId: item.id } });
  } catch (error) {}
}

// Видимость переключается прямо в списке, без подтверждения: действие
// обратимое. Поля записи при этом не трогаем — уходит только visible.
async function toggleVisible(item) {
  try {
    const saved = await apiFeed({ command: 'itemSave', id: item.id, visible: !item.visible });

    item.visible = saved.visible;
  } catch (error) {}
}

/**
 * Удаление записи.
 *
 * false — не ошибка, а ответ «на запись ссылаются»: тогда спрашиваем, кто
 * именно её держит, и показываем список. Разбирать цепочку оператор пойдёт сам.
 */
async function removeItem(item) {
  if (!(await document.confirmDialog(t('feed_item_delete_ask') + ' id ' + item.id))) return;

  try {
    const done = await apiFeed({ command: 'itemDelete', id: item.id });

    if (done) {
      await loadItems();

      return;
    }

    const holders = await apiFeed({ command: 'itemLinks', id: item.id });

    document.showToast(
      t('feed_item_held') + ' ' + holders.map((holder) => holder.feedTitle + ' · ' + holder.title).join(', '),
      'error',
    );
  } catch (error) {}
}

/**
 * Поиск по записям.
 *
 * Условия соединяются через И, как в билдере. Искать можно по полям со своей
 * колонкой: значения из json-контейнера сервер отбирать не умеет, поэтому в
 * выбор они не идут — вместе с ними уходят и картинки.
 *
 * Проценты у like подставляет сервер: оператор ищет кусок текста, а не пишет
 * маску.
 */
const OPS = {
  string: ['like', '='],
  bigint: ['=', '<>', '>', '>=', '<', '<='],
  date: ['=', '<>', '>', '>=', '<', '<='],
  bool: ['='],
  link: ['='],
};

const searchMode = ref(false);
const filters = ref([]);

// поля, по которым сервер умеет искать: слоты этой ленты
const searchFields = computed(() =>
  (feed.value.schema?.fields ?? [])
    .filter((field) => typeof field.column === 'string' && field.column.startsWith('__') && field.code)
    .map((field) => ({
      code: field.code,
      label: field.label || field.code,
      type: (field.column.match(/^__([a-z]+)_/) ?? [])[1] ?? 'string',
    })),
);

// пустое значение — условие ещё не заполнено, в запрос оно не идёт
const activeFilters = computed(() =>
  filters.value
    .filter((filter) => String(filter.value ?? '').trim() !== '')
    .map((filter) => ({ field: filter.field, op: filter.op, value: filter.value })),
);

function opsOf(code) {
  return OPS[searchFields.value.find((field) => field.code === code)?.type] ?? ['='];
}

function addFilter() {
  const field = searchFields.value[0];

  if (!field) return;

  filters.value.push({ field: field.code, op: opsOf(field.code)[0], value: '' });
}

// сравнения у типов разные, поэтому со сменой поля берём первое подходящее
function onFilterField(filter) {
  filter.op = opsOf(filter.field)[0];
}

function removeFilter(index) {
  filters.value.splice(index, 1);
}

function toggleSearch() {
  searchMode.value = !searchMode.value;

  if (searchMode.value) {
    if (filters.value.length === 0) addFilter();

    return;
  }

  filters.value = [];
  applyFilters();
}

// найденное всегда с первой страницы: на старой записей может уже не быть
async function applyFilters() {
  page.value = 1;

  try {
    await loadItems();
  } catch (error) {}
}

/**
 * Групповые операции.
 *
 * Пока режим включён, вместо ручки перетаскивания стоит чекбокс: порядок и
 * отметки — разные задачи, и одна колонка не может быть и тем, и другим.
 */
const groupMode = ref(false);
const marked = ref([]);

function toggleGroupMode() {
  groupMode.value = !groupMode.value;
  marked.value = [];
}

// «выделить все» — про то, что на экране: отмечать записи со следующих
// страниц оператор не видит, а удалять их пришлось бы вслепую
const allMarked = computed(
  () => items.value.length > 0 && items.value.every((item) => marked.value.includes(item.id)),
);

function toggleAll() {
  marked.value = allMarked.value ? [] : items.value.map((item) => item.id);
}

function toggleMark(id) {
  marked.value = marked.value.includes(id)
    ? marked.value.filter((item) => item !== id)
    : [...marked.value, id];
}

/**
 * Удаление отмеченных. Удерживаемые ссылками остаются, и сервер возвращает
 * именно их: удаляем что можно, остальное показываем.
 */
async function removeMarked() {
  if (marked.value.length === 0) return;

  if (!(await document.confirmDialog(t('feed_items_delete_ask') + ' ' + marked.value.length))) return;

  try {
    const data = await apiFeed({ command: 'itemsDelete', feedId: feedId.value, ids: marked.value });

    marked.value = data.skipped.map((item) => item.id);

    document.showToast(t('feed_items_deleted') + ' ' + data.deleted);

    if (data.skipped.length > 0) {
      document.showToast(
        t('feed_item_held') + ' ' + data.skipped.map((item) => item.title).join(', '),
        'error',
      );
    }

    await loadItems();
  } catch (error) {}
}

/**
 * Порядок записей. dropIndex — место внутри страницы, а позиция в ленте
 * сквозная, поэтому прибавляем то, что осталось на предыдущих страницах.
 *
 * Список идёт от больших номеров к меньшим, а позиции нумеруются с нуля,
 * поэтому место на экране считается от конца ленты: itemMove перенумеровывает
 * её целиком, и номера всегда лежат подряд.
 */
async function onReorder(event) {
  const item = event.value[event.dropIndex];
  items.value = event.value;

  const place = (page.value - 1) * PER_PAGE + event.dropIndex;

  try {
    await apiFeed({
      command: 'itemMove',
      id: item.id,
      position: direction.value === 'desc' ? total.value - 1 - place : place,
    });

    await loadItems();
  } catch (error) {}
}

async function toPage(next) {
  page.value = Math.min(Math.max(1, next), lastPage.value);

  try {
    await loadItems();
  } catch (error) {}
}

watch(feedId, () => {
  page.value = 1;
  load();
});

onMounted(() => {
  load();
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink
        :to="feed.group_id ? { name: 'dataFeeds', params: { groupId: feed.group_id } } : { name: 'data' }"
        class="text-decoration-none me-2"
      >
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      {{ feed.title }}
      <button class="btn btn-sm btn-success py-0 ms-2" :title="t('add')" @click="createItem()">
        <i class="fas fa-plus small"></i>
      </button>

      <!-- отсюда правят записи, а схему полей — там -->
      <RouterLink
        :to="{ name: 'feed', params: { feedId: feedId } }"
        class="ms-3 fs-6 text-decoration-none"
        :title="t('feed_tab_structure')"
      >
        <i class="fas fa-cog"></i>
      </RouterLink>

      <button
        class="btn btn-sm py-0 ms-2 fs-6"
        :class="groupMode ? 'btn-secondary' : 'btn-outline-secondary'"
        :title="t('feed_group_ops')"
        @click="toggleGroupMode()"
      >
        <i class="fas fa-tasks"></i>
      </button>

      <button
        class="btn btn-sm py-0 ms-2 fs-6"
        :class="searchMode ? 'btn-secondary' : 'btn-outline-secondary'"
        :title="t('search')"
        @click="toggleSearch()"
      >
        <i class="fas fa-search"></i>
      </button>
    </h1>

    <div v-if="groupMode" class="d-flex align-items-center gap-2 mb-3">
      <button
        class="btn btn-sm btn-outline-danger"
        :disabled="marked.length === 0"
        :title="t('delete')"
        @click="removeMarked()"
      >
        <i class="fas fa-trash"></i>
      </button>

      <label class="d-flex align-items-center gap-1 mb-0" role="button">
        <input type="checkbox" class="form-check-input mt-0" :checked="allMarked" @change="toggleAll()" />
        {{ t('feed_mark_all') }}
      </label>
    </div>

    <div v-if="searchMode" class="mb-3">
      <div v-for="(filter, index) in filters" :key="index" class="d-flex align-items-center gap-2 mb-2">
        <select
          v-model="filter.field"
          class="form-select form-select-sm w-auto"
          @change="onFilterField(filter)"
        >
          <option v-for="field in searchFields" :key="field.code" :value="field.code">
            {{ field.label }}
          </option>
        </select>

        <select v-model="filter.op" class="form-select form-select-sm w-auto">
          <option v-for="op in opsOf(filter.field)" :key="op" :value="op">{{ op }}</option>
        </select>

        <input v-model="filter.value" class="form-control form-control-sm w-auto" @keyup.enter="applyFilters()" />

        <button class="btn btn-success btn-sm py-0 px-1 lh-1" :title="t('add')" @click="addFilter()">
          <i class="fas fa-plus" style="font-size: 0.7rem"></i>
        </button>

        <button class="btn btn-sm btn-link text-danger py-0" :title="t('delete')" @click="removeFilter(index)">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <button class="btn btn-sm btn-primary" @click="applyFilters()">
        <i class="fas fa-search me-1"></i>{{ t('search') }}
      </button>
    </div>

    <div v-if="total === 0" class="text-muted">{{ t('feed_no_items') }}</div>

    <template v-else>
      <DataTable :value="items" @row-reorder="onReorder" size="small">
        <Column v-if="!groupMode && byPosition" rowReorder headerStyle="width: 3rem" />

        <!-- список стоит не на своём порядке: тащить нечего, но вернуться можно -->
        <Column v-else-if="!groupMode" headerStyle="width: 3rem">
          <template #header>
            <i class="fas fa-arrows-alt-v text-muted" role="button" :title="t('feed_order_own')" @click="sortBy('__position', 'desc')"></i>
          </template>
        </Column>

        <Column v-else headerStyle="width: 3rem">
          <template #body="{ data }">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="marked.includes(data.id)"
              @change="toggleMark(data.id)"
            />
          </template>
        </Column>
        <Column headerStyle="width: 4rem">
          <template #body="{ data }">
            <RouterLink
              :to="{ name: 'dataItem', params: { itemId: data.id } }"
              class="text-decoration-none"
              :title="t('edit')"
            >
              <i class="fas fa-pen"></i>
            </RouterLink>
          </template>
        </Column>
        <Column
          v-for="column in columns"
          :key="column.code"
          :headerStyle="column.type === 'image' ? 'width: 5rem' : undefined"
        >
          <template #header>
            <span
              v-if="sortable(column)"
              role="button"
              class="me-1"
              :class="sorted(column.code, 'asc') ? 'text-primary' : 'text-muted'"
              @click="sortBy(column.code, 'asc')"
              >&uarr;</span
            >

            {{ column.label || column.code }}

            <span
              v-if="sortable(column)"
              role="button"
              class="ms-1"
              :class="sorted(column.code, 'desc') ? 'text-primary' : 'text-muted'"
              @click="sortBy(column.code, 'desc')"
              >&darr;</span
            >
          </template>

          <template #body="{ data }">
            <template v-if="column.type === 'image'">
              <img
                v-if="data.fields[column.code]?.url"
                :src="data.fields[column.code].url"
                :alt="data.fields[column.code].alt ?? ''"
                class="border rounded"
                style="max-height: 2.5rem; max-width: 4rem"
              />
            </template>

            <!-- текст показываем так же, как его правят: разметку рендерим -->
            <div v-else-if="column.editor === 'html'" v-html="data.fields[column.code]"></div>
            <div
              v-else-if="column.editor === 'markdown'"
              v-html="markdownHtml[data.id + ':' + column.code]"
            ></div>

            <!-- пока заголовок не пришёл, стоит id: это лучше пустой ячейки -->
            <template v-else-if="column.type === 'link'">
              {{ linkText[column.relation?.feed_id + ':' + data.fields[column.code]] ?? data.fields[column.code] }}
            </template>

            <template v-else-if="column.type === 'datetime' && column.listFormat">
              {{ formatDate(data.fields[column.code], column.listFormat) }}
            </template>

            <template v-else>{{ data.fields[column.code] }}</template>
          </template>
        </Column>
        <Column :header="t('feed_visible')" headerStyle="width: 7rem">
          <template #body="{ data }">
            <i
              role="button"
              :title="t('feed_visible')"
              :class="data.visible ? 'fas fa-eye text-success' : 'fas fa-eye-slash text-muted'"
              @click="toggleVisible(data)"
            ></i>
          </template>
        </Column>
        <Column headerStyle="width: 4rem">
          <template #body="{ data }">
            <i class="fas fa-trash" role="button" :title="t('delete')" @click="removeItem(data)"></i>
          </template>
        </Column>
      </DataTable>

      <div v-if="lastPage > 1" class="d-flex align-items-center gap-2 mt-2">
        <button class="btn btn-sm btn-outline-primary" :disabled="page <= 1" @click="toPage(page - 1)">
          <i class="fas fa-arrow-left"></i>
        </button>
        <span class="text-muted small">{{ page }} / {{ lastPage }}</span>
        <button class="btn btn-sm btn-outline-primary" :disabled="page >= lastPage" @click="toPage(page + 1)">
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>

      <div class="text-muted small mt-2">{{ t('feed_items_count') }}: {{ total }}</div>
    </template>
  </div>
</template>

<style scoped></style>
