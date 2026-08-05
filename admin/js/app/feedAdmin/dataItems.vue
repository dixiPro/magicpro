<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
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
const imageCode = ref(''); // первое поле-изображение: его миниатюра идёт в список
const items = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

// колонки берём из схемы: контейнер __data пропускаем, его поля показывать
// в списке незачем — там тексты
async function loadFeed() {
  const data = await apiFeed({ command: 'feedGet', id: feedId.value });

  feed.value = data;

  columns.value = (data.schema?.fields ?? [])
    .filter((field) => field.column && field.column !== '__data')
    .map((field) => ({ code: field.code, label: field.label }));

  // изображений в ленте может быть несколько, в список берём первое по схеме:
  // строка списка должна оставаться строкой, а не полосой картинок
  const container = (data.schema?.fields ?? []).find((field) => field.column === '__data');

  imageCode.value = (container?.data ?? []).find((field) => field.type === 'image')?.code ?? '';
}

async function loadItems() {
  const data = await apiFeed({
    command: 'itemsList',
    feedId: feedId.value,
    page: page.value,
    perPage: PER_PAGE,
  });

  items.value = data.items;
  lastPage.value = data.lastPage;
  total.value = data.total;
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
 * Порядок записей. dropIndex — место внутри страницы, а позиция в ленте
 * сквозная, поэтому прибавляем то, что осталось на предыдущих страницах.
 */
async function onReorder(event) {
  const item = event.value[event.dropIndex];
  items.value = event.value;

  try {
    await apiFeed({
      command: 'itemMove',
      id: item.id,
      position: (page.value - 1) * PER_PAGE + event.dropIndex,
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
    </h1>

    <div v-if="total === 0" class="text-muted">{{ t('feed_no_items') }}</div>

    <template v-else>
      <DataTable :value="items" @row-reorder="onReorder" size="small">
        <Column rowReorder headerStyle="width: 3rem" />
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
        <Column v-if="imageCode" headerStyle="width: 5rem">
          <template #body="{ data }">
            <img
              v-if="data.fields[imageCode]?.url"
              :src="data.fields[imageCode].url"
              :alt="data.fields[imageCode].alt ?? ''"
              class="border rounded"
              style="max-height: 2.5rem; max-width: 4rem"
            />
          </template>
        </Column>
        <Column v-for="column in columns" :key="column.code" :header="column.label || column.code">
          <template #body="{ data }">{{ data.fields[column.code] }}</template>
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
