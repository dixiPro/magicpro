<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import { useFeedStore } from './store.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Готовые куски кода для блейда статьи.
 *
 * Ничего не выполняется и никуда не сохраняется: это образец, который копируют
 * в статью и правят под себя. Имена полей берутся из схемы открытой ленты,
 * поэтому переписывать их за примером не приходится.
 *
 * Код идёт одним куском, без деления на php и html: в статью он вставляется
 * целиком. Кнопка копирует всё; нужна часть — оператор выделит её сам.
 *
 * Рецепты: запись по id (ленту знать не нужно, запись знает её сама) и список
 * записей ленты с пагинацией.
 */
const store = useFeedStore();

/**
 * Ключ открытого рецепта или null. Ключ, а не сам рецепт: код поиска
 * пересобирается на каждое изменение условий, и объект сразу устаревает.
 */
const current = ref(null);

const open = computed({
  get: () => current.value !== null,
  set: (value) => {
    if (! value) current.value = null;
  },
});

// вывод поля зависит от типа: дату форматируем, картинку показываем, текст
// отдаём как есть — он хранится так, как его ввёл оператор
function valueOf(field) {
  if (field.type === 'date' || field.type === 'datetime') {
    return `{{ $item->${field.code}?->format('d.m.Y') }}`;
  }

  if (field.type === 'bool' || field.type === 'boolean') {
    return `{{ $item->${field.code} ? 'yes' : 'no' }}`;
  }

  // в поле картинки лежат url, alt, x, y, mime и size. Размеры ставим в
  // атрибуты: без них браузер не знает место под картинку и страница прыгает
  if (field.type === 'image') {
    return `<img src="{{ $item->${field.code}['url'] ?? '' }}"
         alt="{{ $item->${field.code}['alt'] ?? '' }}"
         width="{{ $item->${field.code}['x'] ?? '' }}"
         height="{{ $item->${field.code}['y'] ?? '' }}">
    {{-- mime {{ $item->${field.code}['mime'] ?? '' }}, size {{ $item->${field.code}['size'] ?? 0 }} bytes --}}`;
  }

  if (field.type === 'text') {
    return `{!! $item->${field.code} !!}`;
  }

  return `{{ $item->${field.code} }}`;
}

// поля записи, каждое своим блоком. Одни и те же строки идут и в одиночную
// запись, и в цикл списка — там они сдвигаются на отступ
const rows = computed(() =>
  store.allFields
    .filter((field) => field.code !== '')
    .map((field) => `<div>\n    ${valueOf(field)}\n</div>`)
    .join('\n')
);

function indent(text) {
  return text
    .split('\n')
    .map((line) => (line === '' ? line : '    ' + line))
    .join('\n');
}

const byId = computed(() => {
  return `@php
    // id from the route: /article/id/5 or /article?id=5
    $id = (int) ($Get['id'] ?? 0);

    $item = $id
        ? FeedItem::find($id)   // the record or null; the feed is not needed, the record knows it
        : null;
@endphp

@if (! $item)
    <p>Record not found</p>
@else

${rows.value}

@endif`;
});

// значение-заготовка по типу поля: оператор всё равно поставит своё
function constOf(field) {
  if (field.type === 'bigint' || field.type === 'integer') return '100';
  if (field.type === 'decimal') return '9.99';
  if (field.type === 'date' || field.type === 'datetime') return `'2026-01-01 00:00:00'`;
  if (field.type === 'bool' || field.type === 'boolean') return 'true';
  if (field.type === 'link') return '1';
  if (field.type === 'json') return `['key' => 'value']`;

  return `'value'`;
}

/**
 * Список «поле => значение» для create() и update(). Картинки в нём нет: у неё
 * файл, а не значение.
 */
function valueList(indent, extra = []) {
  const fields = store.allFields.filter((field) => field.code !== '' && field.type !== 'image');

  const pairs = [...extra, ...fields.map((field) => [`'${field.code}'`, constOf(field)])];

  const width = Math.max(0, ...pairs.map(([name]) => name.length));

  return pairs
    .map(([name, value]) => `${indent}${name}${' '.repeat(width - name.length)} => ${value},`)
    .join('\n');
}

const create = computed(() => {
  return `@php
    $item = Feed::where('code', '${store.code}')->first()   // the feed ${store.code} or null
        ?->items()                                  // its records and its schema
        ->create([
${valueList('            ', [['\'__visible\'', 'true']])}
        ]);
@endphp

@if (! $item)
    <p>Feed not found</p>
@else
    <p>Record created, id {{ $item->id }}</p>
@endif`;
});

const change = computed(() => {
  const values = valueList('            ');

  return `@php
    // id from the route: /article/id/5 or /article?id=5
    $id = (int) ($Get['id'] ?? 0);

    $item = $id
        ? FeedItem::find($id)   // the record or null; the feed is not needed, the record knows it
        : null;

    if ($item) {
        $item->update([
${values}
        ]);
    }
@endphp

@if (! $item)
    <p>Record not found</p>
@else
    <p>Record changed</p>
@endif`;
});

const remove = `@php
    // id from the route: /article/id/5 or /article?id=5
    $id = (int) ($Get['id'] ?? 0);

    $item = $id
        ? FeedItem::find($id)   // the record or null; the feed is not needed, the record knows it
        : null;

    $error = '';

    if ($item) {
        try {
            $item->delete();                 // the files of the record go with it
        } catch (\\RuntimeException $e) {
            $error = $e->getMessage();       // the record is linked to, it is not deleted
        }
    }
@endphp

@if (! $item)
    <p>Record not found</p>
@elseif ($error)
    <p>{{ $error }}</p>
@else
    <p>Record deleted</p>
@endif`;

/**
 * Страница записей ленты: строки, которые собрал оператор, и вывод найденного.
 *
 * Без пагинации цепочка кончается на get(), и служебный блок со страницами
 * уходит вместе с ней — у коллекции этих полей нет.
 */
function pageCode(chain, paginate = true, onPage = 20) {
  const middle = chain.map((line) => '        ' + line).join('\n');

  const head = paginate
    ? `    // page number: /article/page/2 or /article?page=2, allow page in the route
    $page = (int) ($Get['page'] ?? 1);

`
    : '';

  const tail = paginate ? `->paginate(${onPage || 20}, ['*'], 'page', $page);` : `->get();`;

  const pages = paginate
    ? `
@if ($items->hasPages())
<nav class="mt-3">
    <ul class="pagination">
        <li class="page-item @if ($items->onFirstPage()) disabled @endif">
            <a class="page-link" href="{{ $items->previousPageUrl() }}">&laquo;</a>
        </li>

        @foreach ($items->getUrlRange(1, $items->lastPage()) as $number => $url)
        <li class="page-item @if ($number == $items->currentPage()) active @endif">
            <a class="page-link" href="{{ $url }}">{{ $number }}</a>
        </li>
        @endforeach

        <li class="page-item @if (! $items->hasMorePages()) disabled @endif">
            <a class="page-link" href="{{ $items->nextPageUrl() }}">&raquo;</a>
        </li>
    </ul>
</nav>
@endif
`
    : '';

  return `@php
${head}    $items = Feed::where('code', '${store.code}')->first()   // the feed ${store.code} or null
        ?->items()                    // its records and its schema
${middle === '' ? '' : middle + '\n'}        ${tail}
@endphp

@if (! $items || $items->isEmpty())
    <p>No records</p>
@else

@foreach ($items as $item)
${indent(rows.value)}
@endforeach
${pages}
@endif`;
}

// === билдер списка ===

// сравнения по типу поля
const COMPARE = {
  string: ['=', 'like'],
  bigint: ['=', '<>', '>', '>=', '<', '<='],
  date: ['=', '<>', '>', '>=', '<', '<='],
  link: ['='],
  bool: ['= true', '= false'],
};

// колонки, которых нет в схеме, но искать и сортировать по ним можно
const systemFields = computed(() => [
  { name: '__visible', code: '__visible', type: 'bool', label: t('feed_field_visible') },
  { name: 'created_at', code: 'created_at', type: 'date', label: t('feed_field_created') },
  { name: 'updated_at', code: 'updated_at', type: 'date', label: t('feed_field_updated') },
]);

// в условия и сортировку идут поля со своей колонкой
const searchFields = computed(() => [
  ...store.allFields.filter(
    (field) => typeof field.name === 'string' && field.name.startsWith('__') && field.code !== ''
  ),
  ...systemFields.value,
]);

// видимость нужна почти всегда, поэтому стоит сразу; крестик её убирает
const conditions = ref([{ code: '__visible', compare: '= true' }]);
const sorts = ref([]);

// пагинация тоже строка запроса: сняли — вместо paginate() будет get()
const paginate = ref(true);
const perPage = ref(20);

// __visible стоит в условиях отдельной строкой, поэтому в выборе поля его нет
const selectFields = computed(() => searchFields.value.filter((field) => field.code !== '__visible'));

function fieldOf(code) {
  return searchFields.value.find((field) => field.code === code) ?? null;
}

function comparesOf(code) {
  return COMPARE[fieldOf(code)?.type] ?? ['='];
}

function addCondition() {
  const field = selectFields.value[0];

  if (! field) return;

  conditions.value.push({ code: field.code, compare: comparesOf(field.code)[0] });
}

function addSort() {
  const field = selectFields.value[0];

  if (! field) return;

  sorts.value.push({ code: field.code, desc: false });
}

// сравнения у типов разные, поэтому со сменой поля берём первое подходящее
function onFieldChange(condition) {
  condition.compare = comparesOf(condition.code)[0];
}

function conditionLine(condition) {
  const field = fieldOf(condition.code);

  if (! field) return '';

  if (field.type === 'bool') {
    return `->where('${condition.code}', '=', ${condition.compare === '= false' ? 'false' : 'true'})`;
  }

  if (condition.compare === 'like') {
    return `->where('${condition.code}', 'like', '%value%')`;
  }

  return `->where('${condition.code}', '${condition.compare}', 'value')`;
}

const search = computed(() =>
  pageCode(
    [
      ...conditions.value.map(conditionLine).filter((line) => line !== ''),
      ...sorts.value.map((sort) =>
        sort.desc ? `->orderByDesc('${sort.code}')` : `->orderBy('${sort.code}')`
      ),
    ],
    paginate.value,
    perPage.value
  )
);

const recipes = computed(() => [
  { key: 'byId', title: t('feed_recipe_by_id'), code: byId.value },
  { key: 'create', title: t('feed_recipe_create'), code: create.value },
  { key: 'change', title: t('feed_recipe_change'), code: change.value },
  { key: 'remove', title: t('feed_recipe_remove'), code: remove },
  { key: 'search', title: t('feed_recipe_list'), code: search.value },
]);

const recipe = computed(() => recipes.value.find((item) => item.key === current.value) ?? null);

/**
 * Копирование через скрытую textarea.
 *
 * navigator.clipboard есть только в защищённом контексте, а админка открыта по
 * http, и там его просто нет. execCommand объявлен устаревшим, но работает
 * везде — в apiCall.js копирование сделано так же.
 */
function copyAll() {
  const textarea = document.createElement('textarea');

  textarea.value = recipe.value?.code ?? '';
  textarea.style.position = 'fixed';
  textarea.style.opacity = '0';

  document.body.appendChild(textarea);
  textarea.select();

  try {
    const done = document.execCommand('copy');

    document.showToast(done ? t('copy') : t('error'), done ? 'success' : 'error');
  } catch (error) {
    document.showToast(error.message ?? error, 'error');
  } finally {
    document.body.removeChild(textarea);
  }
}
</script>

<template>
  <div>
    <ul class="list-unstyled">
      <li v-for="item in recipes" :key="item.key">
        <a href="#" class="text-decoration-none" @click.prevent="current = item.key">
          {{ item.title }}
        </a>
      </li>
    </ul>

    <Dialog
      v-model:visible="open"
      modal
      :draggable="false"
      :header="recipe?.title ?? ''"
      :style="{ width: '60rem' }"
    >
      <div v-if="current === 'search'" class="mb-3">
        <table class="table table-sm w-auto mb-2">
          <thead>
            <tr>
              <th>
                {{ t('feed_conditions') }}
                <button class="btn btn-success py-0 px-1 ms-1 lh-1" :title="t('add')" @click="addCondition()">
                  <i class="fas fa-plus" style="font-size: 0.7rem"></i>
                </button>
              </th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(condition, index) in conditions" :key="index">
              <td>
                <span v-if="condition.code === '__visible'">__visible</span>

                <select
                  v-else
                  v-model="condition.code"
                  class="form-select form-select-sm"
                  @change="onFieldChange(condition)"
                >
                  <option v-for="field in selectFields" :key="field.code" :value="field.code">
                    {{ field.code }} · {{ field.label }}
                  </option>
                </select>
              </td>
              <td>
                <select v-model="condition.compare" class="form-select form-select-sm">
                  <option v-for="compare in comparesOf(condition.code)" :key="compare" :value="compare">
                    {{ compare }}
                  </option>
                </select>
              </td>
              <td>
                <button class="btn btn-sm btn-link text-danger py-0" @click="conditions.splice(index, 1)">
                  <i class="fas fa-times"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <table class="table table-sm w-auto mb-0">
          <thead>
            <tr>
              <th>
                {{ t('feed_sorting') }}
                <button class="btn btn-success py-0 px-1 ms-1 lh-1" :title="t('add')" @click="addSort()">
                  <i class="fas fa-plus" style="font-size: 0.7rem"></i>
                </button>
              </th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sort, index) in sorts" :key="index">
              <td>
                <select v-model="sort.code" class="form-select form-select-sm">
                  <option v-for="field in selectFields" :key="field.code" :value="field.code">
                    {{ field.code }} · {{ field.label }}
                  </option>
                </select>
              </td>
              <td>
                <select v-model="sort.desc" class="form-select form-select-sm">
                  <option :value="false">{{ t('feed_sort_asc') }}</option>
                  <option :value="true">{{ t('feed_sort_desc') }}</option>
                </select>
              </td>
              <td>
                <button class="btn btn-sm btn-link text-danger py-0" @click="sorts.splice(index, 1)">
                  <i class="fas fa-times"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <table class="table table-sm w-auto mb-0">
          <thead>
            <tr>
              <th>
                {{ t('feed_pagination') }}
                <button
                  v-if="! paginate"
                  class="btn btn-success py-0 px-1 ms-1 lh-1"
                  :title="t('add')"
                  @click="paginate = true"
                >
                  <i class="fas fa-plus" style="font-size: 0.7rem"></i>
                </button>
              </th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginate">
              <td class="d-flex align-items-center gap-2">
                <input
                  v-model.number="perPage"
                  type="number"
                  min="1"
                  class="form-control form-control-sm"
                  style="width: 5rem"
                />
                {{ t('feed_per_page_after') }}
              </td>
              <td>
                <button class="btn btn-sm btn-link text-danger py-0" @click="paginate = false">
                  <i class="fas fa-times"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <button class="btn btn-sm btn-outline-primary mb-2" :title="t('copy')" @click="copyAll()">
        <i class="fas fa-copy"></i>
      </button>

      <pre class="border rounded p-2"><code>{{ recipe?.code }}</code></pre>
    </Dialog>
  </div>
</template>

<style scoped>
pre {
  overflow-x: auto;
  font-size: 0.9rem;
  line-height: 1.3;
}
</style>
